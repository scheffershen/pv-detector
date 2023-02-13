<?php

namespace App\Controller\RevueManagement;

use App\Entity\RevueManagement\Image;
use App\Entity\RevueManagement\Numero;
use App\Entity\RevueManagement\Revue;
use App\Form\RevueManagement\NumeroType;
use App\Form\RevueManagement\NumeroDeleteType;
use App\Manager\RevueManagement\ImageManager;
use App\Manager\RevueManagement\ZipManager;
use App\Message\RevueManagement\NumeroMessage;
use App\Message\RevueManagement\NumeroUpdateMessage;
use App\Repository\RevueManagement\ImageRepository;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\RevueManagement\PageRepository;
use App\Service\RevueManagement\NumeroService;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\Event\ORMAdapterQueryEvent;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapterEvents;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigStringColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Spatie\PdfToImage\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;

/**
 * @Route("/admin/numero")
 */
class NumeroController extends AbstractController
{
    private $kernel;
    private $messageBus;
    private $slugger;
    private $translator;
    private $imageManager;
    private $zipManager;
    private $imageRepository;
    private $numeroRepository;
    private $serializer;
    private $numeroService;

    public function __construct(MessageBusInterface $messageBus, TranslatorInterface $translator, KernelInterface $kernel, SluggerInterface $slugger, ImageManager $imageManager, ZipManager $zipManager, ImageRepository $imageRepository, NumeroRepository $numeroRepository, SerializerInterface $serializer, NumeroService $numeroService)
    {
        $this->kernel = $kernel;
        $this->messageBus = $messageBus;
        $this->slugger = $slugger;
        $this->translator = $translator;
        $this->imageManager = $imageManager;
        $this->zipManager = $zipManager;
        $this->imageRepository = $imageRepository;
        $this->numeroRepository = $numeroRepository;
        $this->serializer = $serializer;
        $this->numeroService = $numeroService;
    }

    /**
     * replaced by admin_numero_omines
     * 
     * @Route("/index", name="admin_numero_index", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function index(NumeroRepository $numeroRepository): Response
    {
        //return $this->redirectToRoute('admin_numero_omines');
        return $this->render('RevueManagement/Numero/index.html.twig', [
            'numeros' => $numeroRepository->findBy(['isDeleted' => false], ['updateDate' => 'DESC']),
        ]);
    }

    /**
     * @Route("/new/{id}", name="admin_revue_new_numero", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function new(Request $request, Revue $revue): Response
    {
        $numero = new Numero();
        $numero->setRevue($revue);

        $form = $this->createForm(NumeroType::class, $numero);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($result = $this->numeroService->create($form, $numero)) {
                return $this->redirectToRoute('admin_numero_show', ['id' => $numero->getId()]);
            }
        }

        return $this->render('RevueManagement/Numero/new.html.twig', [
            'numero' => $numero,
            'form' => $form->createView(),
            'revue' => $revue
        ]);
    }

    /**
     * @Route("/edit/{id}", name="admin_numero_edit", methods={"GET","POST"})
     * Security("is_granted('ROLE_GESTION_NUMEROS')")
     * @IsGranted("NUMERO_EDIT", subject="numero", message="You cannot edit this numero.")
     */
    public function edit(Request $request, Numero $numero, PageRepository $pageRepository): Response
    {
        $form = $this->createForm(NumeroType::class, $numero);
        $form->handleRequest($request);

        $old_numero = $this->serializer->serialize($numero, 'json', ['groups' => 'detail']);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($result = $this->numeroService->update($form, $numero, $old_numero)) {
                return $this->redirectToRoute('admin_numero_show', ['id' => $numero->getId()]);
            }
        }

        return $this->render('RevueManagement/Numero/edit.html.twig', [
            'numero' => $numero,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit_flash/{id}", name="admin_numero_edit_flash", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function editFlash(Request $request,  Numero $numero): Response
    {
        $em = $this->getDoctrine()->getManager();
        $output = [];
        $output['id'] = $request->get('pk');
        $output['name'] = $request->get('name');
        $output['value'] = $request->get('value');

        switch ($request->get('name')) {
            case 'description':
                $numero->setDescription($request->get('value'));
                break;
            default:
                break;
        }

        $em->persist($numero);
        $em->flush();

        return new JsonResponse($output, 200);
    }

    /**
     * @Route("/show/{id}", name="admin_numero_show", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function show(Request $request, Numero $numero): Response
    {
        $form = $this->createForm(NumeroDeleteType::class, $numero);

        return $this->render('RevueManagement/Numero/show.html.twig', [
            'numero' => $numero,
            'form' => $form->createView(),
            'action' => $request->get('action'),
        ]);
    }

    /**
     * Desactive/Active un numero.
     *
     * @Route("/disable/{id}", name="admin_numero_disable", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function disable(Numero $numero): Response
    {
        $em = $this->getDoctrine()->getManager();

        if ($numero->getIsValid()) {
            $numero->setIsValid(false);
            $this->addFlash('success', $this->translator->trans('numero.flash.disable'));
            $action = 'disable';
        } else {
            $numero->setIsValid(true);
            $this->addFlash('success', $this->translator->trans('numero.flash.enable'));
            $action = 'enable';
        }

        try {
            $numero->setUpdateUser($this->getUser());
            $em->persist($numero);
            $em->flush();
            
            return $this->redirectToRoute('admin_revue_show', ['id' => $numero->getRevue()->getId()]);
        } catch (DBALException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_numero_show', ['id' => $numero->getId(), 'action' => $action]);
    }

    /**
     * numero is the images files.
     *
     * @Route("/pages/{id}", name="admin_numero_pages", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function pages(Request $request, Numero $numero): Response
    {
        $scroll = ($request->get('scroll')) ? 'overflow-y:scroll' : '';
        $no_position = ($request->get('no_position')) ? true : false;
        $origin = ($request->get('origin')) ? true : false;

        return $this->render('RevueManagement/Numero/numero_pages.html.twig', [
            'scroll' => $scroll,
            'no_position' => $no_position,
            'origin' => $origin,
            'numero' => $numero,
            'action' => $request->get('action')
        ]);
    }
    
    /**
     * numero is in a pdf file. (no more use)
     * 
     * @Route("/_pdf2images/{id}", name="admin_numero_pdf2images", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function _pdf2images(Request $request, Numero $numero): Response
    {
        $scroll = ($request->get('scroll')) ? 'overflow-y:scroll' : '';
        $no_position = ($request->get('no_position')) ? true : false;

        return $this->render('RevueManagement/Numero/pdf2images.html.twig', [
            'scroll' => $scroll,
            'no_position' => $no_position,
            'numero' => $numero,
        ]);
    }

    /**
     * numero par image (no more use).
     *
     * @Route("/_image/{id}", name="admin_numero_image", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function _image(Request $request, Image $image): Response
    {
        $scroll = ($request->get('scroll')) ? 'overflow-y:scroll' : '';
        $no_position = ($request->get('no_position')) ? true : false;

        return $this->render('RevueManagement/Numero/numero_image.html.twig', [
            'scroll' => $scroll,
            'no_position' => $no_position,
            'image' => $image,
        ]);
    }

    /**
     * @Route("/omines/{status}", defaults={"status": "enable"}, name="admin_numero_omines", methods={"GET", "POST"})
     * datatables Omines
     * @Security("is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function omines(Request $request, string $status, DataTableFactory $dataTableFactory, Environment $twig): Response
    {
        if (!$twig->hasExtension(StringLoaderExtension::class)) {
            $twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        }

        $format = ($request->getLocale() == "en")?'Y-M-d':'d/M/Y';

        if ($status == "enable") {
            $table = $dataTableFactory->create()                
                ->add('revue', TextColumn::class, ['field' => 'r.title'])
                ->add('title', TextColumn::class)
                ->add('receiptDate', DateTimeColumn::class, ['format' => $format])
                ->add('updateDate', DateTimeColumn::class, ['format' => $format])
                ->add('actions', TwigStringColumn::class, [
                    'template' => '<a class="icon text-secondary" href="{{ url(\'admin_numero_show\', {id: row.id}) }}" title="{{ \'action.show\' | trans }}" target="_blank"><i class="fas fa-eye"></i></a>&nbsp;<a class="icon text-success"  href="{{ url(\'admin_numero_show\', {id: row.id, \'action\': \'disable\'}) }}" title="{{ \'action.disable\'|trans }}" ><i class="fas fa-toggle-on"></i></a>&nbsp;<a class="icon text-secondary" href="{{ url(\'admin_numero_edit\', {id: row.id}) }}" title="{{ \'action.edit\' | trans }}"><i class="fas fa-pencil-alt"></i></a>&nbsp;<a class="icon text-secondary" href="{{ url(\'admin_audit_show_entity_history\', {\'entity\': \'App-Entity-RevueManagement-Numero\', id: row.id}) }}" title="{{ \'action.audit\' | trans }}"><i class="fas fa-code-branch"></i></a>',
                ]);
        } else {
            $table = $dataTableFactory->create()                
                ->add('revue', TextColumn::class, ['field' => 'r.title'])
                ->add('title', TextColumn::class)
                ->add('receiptDate', DateTimeColumn::class, ['format' => $format])
                ->add('updateDate', DateTimeColumn::class, ['format' => $format])
                ->add('actions', TwigStringColumn::class, [
                    'template' => '<a class="icon text-secondary" href="{{ url(\'admin_numero_show\', {id: row.id}) }}" title="{{ \'action.show\' | trans }}" target="_blank"><i class="fas fa-eye"></i></a>&nbsp;<a class="icon text-secondary"  href="{{ url(\'admin_numero_show\', {id: row.id, \'action\': \'enable\'}) }}" title="{{ \'action.enable\'|trans }}" ><i class="fas fa-toggle-off"></i></a>&nbsp;<a class="icon text-secondary" href="{{ url(\'admin_numero_edit\', {id: row.id}) }}" title="{{ \'action.edit\' | trans }}"><i class="fas fa-pencil-alt"></i></a>&nbsp;<a class="icon text-secondary" href="{{ url(\'admin_audit_show_entity_history\', {\'entity\': \'App-Entity-RevueManagement-Numero\', id: row.id}) }}" title="{{ \'action.audit\' | trans }}"><i class="fas fa-code-branch"></i></a>',
                ]);
        }

        $isValid = ($status == "enable")?true:false;

        $table->createAdapter(ORMAdapter::class, [
                'entity' => Numero::class,
                'hydrate' => Query::HYDRATE_ARRAY,
                'query' => function (QueryBuilder $builder) use ($isValid) {
                    $builder
                        ->select('n')
                        ->addSelect('r')
                        ->from(Numero::class, 'n')
                        ->leftJoin('n.revue', 'r')
                        ->where('n.isValid = :isValid')
                        ->setParameter('isValid', $isValid)
                        ->orderBy('n.updateDate', 'DESC');
                },
            ])
            ->handleRequest($request);

        $table->addEventListener(ORMAdapterEvents::PRE_QUERY, function (ORMAdapterQueryEvent $event) {
            $event->getQuery()->useResultCache(true)->useQueryCache(true);
        });

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('RevueManagement/Numero/omines.html.twig', ['status' => $status, 'datatable' => $table]);
    }  
}
