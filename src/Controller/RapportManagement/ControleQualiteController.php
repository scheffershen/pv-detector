<?php

namespace App\Controller\RapportManagement;

use App\Entity\RapportManagement\ControleQualite;
use App\Entity\RapportManagement\Rapport;
use App\Form\RapportManagement\ControleQualiteType;
use App\Repository\RapportManagement\ControleQualiteRepository;
use App\Library\HtmlToDoc;
use App\Library\HtmlToPdfConverter;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use HtmlValidator\Validator; 

/**
 * @Route("/admin/controleQualite")
 */
class ControleQualiteController extends AbstractController
{
    private $translator;
    private $slugger;
    private $converter;

    public function __construct(SluggerInterface $slugger, HtmlToPdfConverter $converter, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->slugger = $slugger;
        $this->converter = $converter;
    }

    /**
     * @Route("/new/{id}", name="admin_controle_qualite_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_CONTROLE_QUALITE_RAPPORT_VEILLE')")
     */
    public function new(Request $request, Rapport $rapport): Response
    {
        $controleQualite = new ControleQualite();
        $controleQualite->setRapport($rapport);

        $form = $this->createForm(ControleQualiteType::class, $controleQualite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            try {
                $controleQualite->setCreateUser($this->getUser());
                $controleQualite->setUpdateUser($this->getUser());  
                              
                $em->persist($controleQualite);
                $em->flush();

                return $this->redirectToRoute('admin_controle_qualite_show', ['id'=>$controleQualite->getId()], Response::HTTP_SEE_OTHER);
            } catch (DBALException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }                
        }

        return $this->render('RapportManagement/ControleQualite/new.html.twig', [
            'controle_qualite' => $controleQualite,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/show/{id}", name="admin_controle_qualite_show", methods={"GET"})
     * @Security("is_granted('ROLE_CONTROLE_QUALITE_RAPPORT_VEILLE')")
     */
    public function show(ControleQualite $controleQualite): Response
    {
        return $this->render('RapportManagement/ControleQualite/show.html.twig', [
            'controle_qualite' => $controleQualite,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="admin_controle_qualite_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_CONTROLE_QUALITE_RAPPORT_VEILLE')")
     */
    public function edit(Request $request, ControleQualite $controleQualite): Response
    {
        $form = $this->createForm(ControleQualiteType::class, $controleQualite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();           
            try {

                $controleQualite->setUpdateUser($this->getUser()); 
                $em->persist($controleQualite);
                $em->flush();

                $this->addFlash('success', $this->translator->trans('controleQualite.flash.updated'));

                return $this->redirectToRoute('admin_controle_qualite_show', ['id'=>$controleQualite->getId()], Response::HTTP_SEE_OTHER);
            } catch (DBALException $e) {
                $this->addFlash('error', $e->getMessage());
            }                
        }

        return $this->render('RapportManagement/ControleQualite/edit.html.twig', [
            'controle_qualite' => $controleQualite,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit_flash/{id}", name="admin_controle_qualite_edit_flash", methods={"GET","POST"})
     * @Security("is_granted('ROLE_CONTROLE_QUALITE_RAPPORT_VEILLE')")
     */
    public function editFlash(Request $request, ControleQualite $controleQualite): Response
    {
        $em = $this->getDoctrine()->getManager();
        $output = [];
        $output['id'] = $request->get('pk');
        $output['name'] = $request->get('name');
        $output['value'] = $request->get('value');

        switch ($request->get('name')) {
            case 'description':
                $controleQualite->setDescription($request->get('value'));
                break;
            default:
                break;
        }

        $em->persist($controleQualite);
        $em->flush();

        return new JsonResponse($output, 200);
    }
    /**
     * @Route("/download/{id}/{format}", name="admin_controle_qualite_download", methods={"GET"})
     * @Security("is_granted('ROLE_CONTROLE_QUALITE_RAPPORT_VEILLE')")
     */
    public function download(Request $request, ControleQualite $controleQualite, string $format): Response
    {
        $config = \HTMLPurifier_Config::createDefault();
        $purifier = new \HTMLPurifier($config);
        $clean_html  = $purifier->purify($controleQualite->getCommentaire());   
             
        switch ($format) {
            case ControleQualite::WORD:
                try {
                    $htd = new HtmlToDoc();        
                    $htd->setTitle($controleQualite->getRapport()->getNumero()->getTitle().' (Contrôle Qualité)');                    
                    $htd->createDoc($clean_html, $this->slugger->slug($controleQualite->getRapport()->getNumero()->getTitle()) . '_rapport_' . (new \DateTime())->format('Y-m-d-H-i-s'), 1); 
                } catch (\Throwable $e) {
                    return new Response($e->getMessage());
                }                       
                break;
            case ControleQualite::PDF:  
                try {
                    $contenu = $this->converter->convertToPdf($clean_html);
                    $response = new Response($contenu);
                    $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->slugger->slug($controleQualite->getRapport()->getNumero()->getTitle()) . '_controle_qualite_' . (new \DateTime())->format('d-M-Y H:i:s'). '.pdf');
                    $response->headers->set('Content-Type', 'application/pdf');
                    $response->headers->set('Content-Disposition', $disposition);
                    return $response;
                } catch (\Throwable $e) {
                    return new Response($e->getMessage());
                }                
                break; 
            case ControleQualite::VALIDATOR:
                $validator = new Validator();
                $result = $validator->validateDocument($clean_html);
                if ($result->hasErrors()) return new Response($result->toHTML());    
                else return new Response($clean_html);                                     
                break;
            case ControleQualite::HTML:
            default:
                return new Response($clean_html);                                   
        } 
        return new Response();                  
    }
}
