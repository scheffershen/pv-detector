<?php

namespace App\Controller\SearchManagement;

use App\Entity\SearchManagement\Dci;
use App\Form\SearchManagement\DciType;
use App\Repository\SearchManagement\DciRepository;
use App\Event\SearchManagement\DciEvent;
use App\Message\SearchManagement\DciMessage;
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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;

/**
 * @Route("/admin/dci")
 */
class DciController extends AbstractController
{
    private $serializer;
    private $translator;
    private $dciRepository;
    private $dispatcher;
    private $messageBus;

    public function __construct(MessageBusInterface $messageBus, TranslatorInterface $translator, DciRepository $dciRepository, SerializerInterface $serializer, EventDispatcherInterface $dispatcher)
    {
        $this->translator = $translator;
        $this->dciRepository = $dciRepository;
        $this->serializer = $serializer;
        $this->dispatcher = $dispatcher;
        $this->messageBus = $messageBus;
    }

    /**
     * replaced by admin_dci_omines
     * 
     * @Route("/index", name="admin_dci_index", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_MOTS_CLES')")
     */
    public function index(): Response
    {
        //return $this->redirectToRoute('admin_dci_omines');
        $response = $this->render('SearchManagement/Dci/index.html.twig', [
            'dcis' => $this->dciRepository->findAll(),
        ]);

        $response->setSharedMaxAge(60);

        return $response;
    }

    /**
     * @Route("/new", name="admin_dci_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_MOTS_CLES')")
     */
    public function new(Request $request): Response
    {
        $dci = new Dci();
        $form = $this->createForm(DciType::class, $dci);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            try {
                $dci->setCreateUser($this->getUser());
                $dci->setUpdateUser($this->getUser());

                foreach ($dci->getClients() as $client) {
                    $client->addDci($dci);
                    $entityManager->persist($client);
                }  

                $entityManager->persist($dci);
                $entityManager->flush();

                $this->addFlash('success', $this->translator->trans('dci.flash.created'));

                return $this->redirectToRoute('admin_dci_show', ['id' => $dci->getId()]);
            } catch (DBALException $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (\Throwable $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('SearchManagement/Dci/new.html.twig', [
            'dci' => $dci,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{id}", name="admin_dci_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_MOTS_CLES')")
     */
    public function edit(Request $request, Dci $dci): Response
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(DciType::class, $dci);
        $form->handleRequest($request);

        //$old_dci_text = $dci->getTitle(); //$this->serializer->serialize($dci, 'json', ['groups' => 'detail']);

        $old_clients = $dci->getClients();
        if ($old_clients) {
            foreach($old_clients as $old_client) {
                $old_client->removeDci($dci);
                $em->persist($old_client);
            }
            $em->flush();
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dci->setUpdateUser($this->getUser());
                
                foreach ($dci->getClients() as $client) {
                    $client->addDci($dci);
                    $em->persist($client);
                } 

                //$_dci = json_decode($old_dci, true);
                //if ($old_dci_text !== $dci->getTitle()) {
                //    $dci->setIsIndexed(false);
                //}

                $em->persist($dci);
                $em->flush();

                $this->messageBus->dispatch(new DciMessage($dci));
                
                $this->addFlash('success', $this->translator->trans('dci.flash.updated'));

                return $this->redirectToRoute('admin_dci_show', ['id' => $dci->getId()]);
            } catch (DBALException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('SearchManagement/Dci/edit.html.twig', [
            'dci' => $dci,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/show/{id}", name="admin_dci_show", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_MOTS_CLES')")
     */
    public function show(Request $request, Dci $dci): Response
    {
        $form = $this->createForm(DciType::class, $dci);

        return $this->render('SearchManagement/Dci/show.html.twig', [
            'dci' => $dci,
            'form' => $form->createView(),
            'action' => $request->get('action'),  
        ]);
    }

    /**
     * Desactive/Active un dci.
     *
     * @Route("/disable/{id}", name="admin_dci_disable", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_MOTS_CLES')")
     */
    public function disable(Dci $dci)
    {
        $em = $this->getDoctrine()->getManager();

        if ($dci->getIsValid()) {
            $dci->setIsValid(false);
            $dci->setIsIndexed(false);
            
            $this->addFlash('success', $this->translator->trans('dci.flash.disable'));
            
            $this->dispatcher->dispatch((new DciEvent($dci)), DciEvent::DESINDEXER);
        } else {
            $dci->setIsValid(true);
            $this->addFlash('success', $this->translator->trans('dci.flash.enable'));
        }

        try {
            $dci->setUpdateUser($this->getUser());
            $em->persist($dci);
            $em->flush();            

        } catch (DBALException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_dci_index');
    }

    private function removeOldIndexs(Dci $dci): void
    {
        $indexs = $this->indexationRepository->findBy(['dci' => $dci]);
        foreach ($indexs as $index) {
            $this->manager->remove($index);
        }
        $this->manager->flush();
    }
    
    /**
     * @Route("/omines/{status}", defaults={"status": "enable"}, name="admin_dci_omines", methods={"GET", "POST"})
     * datatables Omines
     * @Security("is_granted('ROLE_GESTION_MOTS_CLES')")
     */
    public function omines(Request $request, string $status, DataTableFactory $dataTableFactory, Environment $twig): Response
    {
        if (!$twig->hasExtension(StringLoaderExtension::class)) {
            $twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        }

        $format = ($request->getLocale() == "en")?'Y-M-d':'d/M/Y';

        if ($status == "enable") {
            $table = $dataTableFactory->create()                
                ->add('dci', TextColumn::class, ['field' => 'd.title'])
                ->add('categorie', TextColumn::class, ['field' => 'c.title'])
                ->add('actions', TwigStringColumn::class, [
                    'template' => '<a class="icon text-secondary" href="{{ url(\'admin_numero_show\', {id: row.id}) }}" title="{{ \'action.show\' | trans }}" target="_blank"><i class="fas fa-eye"></i></a>&nbsp;<a class="icon text-success"  href="{{ url(\'admin_numero_show\', {id: row.id, \'action\': \'disable\'}) }}" title="{{ \'action.disable\'|trans }}" ><i class="fas fa-toggle-on"></i></a>&nbsp;<a class="icon text-secondary" href="{{ url(\'admin_numero_edit\', {id: row.id}) }}" title="{{ \'action.edit\' | trans }}"><i class="fas fa-pencil-alt"></i></a>&nbsp;<a class="icon text-secondary" href="{{ url(\'admin_audit_show_entity_history\', {\'entity\': \'App-Entity-RevueManagement-Numero\', id: row.id}) }}" title="{{ \'action.audit\' | trans }}"><i class="fas fa-code-branch"></i></a>',
                ]);
        } else {
            $table = $dataTableFactory->create()                
                ->add('dci', TextColumn::class, ['field' => 'd.title'])
                ->add('categorie', TextColumn::class, ['field' => 'c.title'])
                ->add('actions', TwigStringColumn::class, [
                    'template' => '<a class="icon text-secondary" href="{{ url(\'admin_dci_show\', {id: row.id}) }}" title="{{ \'action.show\' | trans }}" target="_blank"><i class="fas fa-eye"></i></a>&nbsp;<a class="icon text-secondary"  href="{{ url(\'admin_dci_show\', {id: row.id, \'action\': \'enable\'}) }}" title="{{ \'action.enable\'|trans }}" ><i class="fas fa-toggle-off"></i></a>&nbsp;<a class="icon text-secondary" href="{{ url(\'admin_dci_edit\', {id: row.id}) }}" title="{{ \'action.edit\' | trans }}"><i class="fas fa-pencil-alt"></i></a>&nbsp;<a class="icon text-secondary" href="{{ url(\'admin_audit_show_entity_history\', {\'entity\': \'App-Entity-SearchManagement-Dci\', id: row.id}) }}" title="{{ \'action.audit\' | trans }}"><i class="fas fa-code-branch"></i></a>',
                ]);
        }

        $isValid = ($status == "enable")?true:false;

        $table->createAdapter(ORMAdapter::class, [
                'entity' => Dci::class,
                'hydrate' => Query::HYDRATE_ARRAY,
                'query' => function (QueryBuilder $builder) use ($isValid) {
                    $builder
                        ->select('d')
                        ->addSelect('c')
                        ->from(Dci::class, 'd')
                        ->leftJoin('d.categorie', 'c')
                        ->where('d.isValid = :isValid')
                        ->setParameter('isValid', $isValid)
                        ->orderBy('d.updateDate', 'DESC');
                },
            ])
            ->handleRequest($request);

        $table->addEventListener(ORMAdapterEvents::PRE_QUERY, function (ORMAdapterQueryEvent $event) {
            $event->getQuery()->useResultCache(true)->useQueryCache(true);
        });

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('SearchManagement/Dci/omines.html.twig', ['status' => $status, 'datatable' => $table]);
    }

    /**
     * replaced by admin_dci_download
     * 
     * @Route("/donwload/{codage}", name="admin_dci_donwload", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_MOTS_CLES')")
     */
    public function download(Request $request, string $codage): Response
    {
        ini_set('memory_limit','-1');
        
        $dcis = $this->dciRepository->findBy(['isValid' => true, 'isDeleted' => false], ['title' => 'ASC']);

        $content = "produit;category;labo\n";
        foreach ($dcis as $dci) {
            $i = 0;
            $labs = "";
            foreach ($dci->getClients() as $client) {
                if ($i == (\count($dci->getClients()) - 1 ) ) {
                    $labs .= $client->getCode();
                } else {
                    $labs .= $client->getCode()."|";
                }
                $i++;
            }
            $content .= $dci->getTitle().";".$dci->getCategorie().";".$labs."\n";
        }
        
        try {
            if (strtolower($codage) == "ansi") {
                return new Response(iconv("UTF-8", "ISO-8859-1//TRANSLIT",$content), 200, [
                    'Content-Type' => 'application/force-download; charset=utf-8',
                    'Content-Disposition' => 'attachement; filename="Mots-clés_ansi' . '.csv'
                ]);
            } else {
                return new Response($content, 200, [
                    'Content-Type' => 'application/force-download; charset=utf-8',
                    'Content-Disposition' => 'attachement; filename="Mots-clés_utf-8' . '.csv'
                ]);                
            }

        } catch (\Throwable $exception) {
            return new Response($exception->getMessage());
        }
    }

}