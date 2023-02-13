<?php

namespace App\Controller\RapportManagement;

use App\Entity\RapportManagement\Rapport;
use App\Entity\RevueManagement\Numero;
use App\Form\RapportManagement\RapportType;
use App\Form\RapportManagement\RapportDeleteType;
use App\Library\HtmlToDoc;
use App\Library\HtmlToPdfConverter;
use App\Repository\RapportManagement\RapportRepository;
use App\Utils\Utils;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use HtmlValidator\Validator; 
use Twig\Environment;

/**
 * @Route("/admin/rapports")
 */
class RapportController extends AbstractController
{
    private $kernel;
    private $messageBus;
    private $twig;
    private $slugger;
    private $translator;
    private $workflow;
    private $converter;

    public function __construct(MessageBusInterface $messageBus, TranslatorInterface $translator, WorkflowInterface $numeroStateMachine, Environment $twig, KernelInterface $kernel, SluggerInterface $slugger, HtmlToPdfConverter $converter)
    {
        $this->kernel = $kernel;
        $this->messageBus = $messageBus;
        $this->slugger = $slugger;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->workflow = $numeroStateMachine;
        $this->converter = $converter;
    }

    /**
     * @Route("/index", name="admin_rapport_index", methods={"GET"})
     * @Security("is_granted('ROLE_RAPPORT_VEILLE')")
     */
    public function index(RapportRepository $rapportRepository): Response
    {
        return $this->render('RapportManagement/Rapport/index.html.twig', [
            'rapports' => $rapportRepository->findBy(['isValid' => true], ['updateDate' => 'DESC']),
        ]);
    }

    /**
     * @Route("/new/{id}", name="admin_rapport_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_RAPPORT_VEILLE_CRUD')")
     */
    public function new(Request $request, Numero $numero): Response
    {
        $rapport = new Rapport();

        $rapport->setNumero($numero);

        $form = $this->createForm(RapportType::class, $rapport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            try {
                $rapport->setCreateUser($this->getUser());
                $rapport->setUpdateUser($this->getUser());

                $this->workflow->apply($numero, 'treatment');

                $entityManager->persist($rapport);
                $entityManager->flush();  

                $this->addFlash('success', $this->translator->trans('rapport.flash.created'));

                return $this->redirectToRoute('admin_rapport_show', ['id'=>$rapport->getId()], Response::HTTP_SEE_OTHER);              
            } catch (DBALException $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (\Throwable $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
            
        }

        return $this->renderForm('RapportManagement/Rapport/new.html.twig', [
            'rapport' => $rapport,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/show/{id}", name="admin_rapport_show", methods={"GET"})
     * @Security("is_granted('ROLE_RAPPORT_VEILLE')")
     */
    public function show(Request $request, Rapport $rapport): Response
    {
        $form = $this->createForm(RapportDeleteType::class, $rapport);

        return $this->render('RapportManagement/Rapport/show.html.twig', [
            'rapport' => $rapport,
            'form' => $form,
            'action' => $request->get('action'),                
        ]);
    }

    /**
     * @Route("/edit/{id}", name="admin_rapport_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_RAPPORT_VEILLE_CRUD')")
     */
    public function edit(Request $request, Rapport $rapport): Response
    {
        $form = $this->createForm(RapportType::class, $rapport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $rapport->setUpdateUser($this->getUser());
            try {
                
                //$this->workflow->apply($rapport->getNumero(), 'treatment');

                $em->persist($rapport);
                $em->flush();

                $this->addFlash('success', $this->translator->trans('rapport.flash.updated'));

                return $this->redirectToRoute('admin_rapport_show', ['id'=>$rapport->getId()]);
            } catch (DBALException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->renderForm('RapportManagement/Rapport/edit.html.twig', [
            'rapport' => $rapport,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/download/{id}/{format}", name="admin_rapport_download", methods={"GET"})
     * @Security("is_granted('ROLE_EXPORT_RAPPORT')")
     */
    public function download(Request $request, Rapport $rapport, string $format): Response
    {
        $config = \HTMLPurifier_Config::createDefault();
        $purifier = new \HTMLPurifier($config);
        $clean_html  = $purifier->purify($rapport->getCommentaire());   
             
        $export = $this->twig->render('RapportManagement/Rapport/export.html.twig', [
                'rapport' => $rapport,
                'content' => Utils::close_open_html_tags($clean_html),
            ]
        );

        switch ($format) {
            case Rapport::WORD:
                try {
                    $htd = new HtmlToDoc();        
                    $htd->setTitle($rapport->getNumero()->getTitle().' (rapport)');                    
                    $htd->createDoc($export, $this->slugger->slug($rapport->getNumero()->getTitle()) . '_rapport_' . (new \DateTime())->format('Y-m-d-H-i-s'), 1); 
                } catch (\Throwable $exception) {
                    return new Response($exception->getMessage());
                }                       
                break;
            case Rapport::PDF:  
                try {
                    $contenu = $this->converter->convertToPdf($export);
                    $response = new Response($contenu);
                    $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->slugger->slug($rapport->getNumero()->getTitle()) . '_rapport_' . (new \DateTime())->format('d-M-Y H:i:s'). '.pdf');
                    $response->headers->set('Content-Type', 'application/pdf;charset=UTF-8');
                    $response->headers->set('Content-Disposition', $disposition);
                    return $response;
                } catch (\Throwable $exception) {
                    return new Response($exception->getMessage());
                }                
                break; 
            case Rapport::VALIDATOR:
                $validator = new Validator();
                $result = $validator->validateDocument($export);
                if ($result->hasErrors()) return new Response($result->toHTML());    
                else return new Response($export);                                     
                break;
            case Rapport::HTML:
            default:
                return new Response($export);                                   
        } 
        return new Response();                  
    }
}
