<?php

namespace App\Controller\RevueManagement;

use App\Entity\RevueManagement\Revue;
use App\Form\RevueManagement\RevueType;
use App\Form\RevueManagement\RevueDeleteType;
use App\Repository\RevueManagement\RevueRepository;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/admin/revue")
 */
class RevueController extends AbstractController
{
    private $translator;
    private $revueRepository;

    public function __construct(TranslatorInterface $translator, RevueRepository $revueRepository)
    {
        $this->translator = $translator;
        $this->revueRepository = $revueRepository;
    }

    /**
     * @Route("/index", name="admin_revue_index", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_REVUES') or is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function index(): Response
    {
        $response = $this->render('RevueManagement/Revue/index.html.twig', [
            'revues' => $this->revueRepository->findBy(['isDeleted' => false], ['title' => 'DESC']),
        ]);

        $response->setSharedMaxAge(60);

        return $response;        
    }

    /**
     * @Route("/new", name="admin_revue_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_REVUES')")
     */
    public function new(Request $request): Response
    {
        $revue = new Revue();
        $form = $this->createForm(RevueType::class, $revue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            try {
                $revue->setCreateUser($this->getUser());
                $revue->setUpdateUser($this->getUser());

                foreach ($revue->getClients() as $client) {
                    $client->addRevue($revue);
                    $entityManager->persist($client);
                }  

                $entityManager->persist($revue);
                $entityManager->flush();

                $this->addFlash('success', $this->translator->trans('revue.flash.created'));

                return $this->redirectToRoute('admin_revue_show', ['id' => $revue->getId()]);
            } catch (DBALException $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (\Throwable $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('RevueManagement/Revue/new.html.twig', [
            'revue' => $revue,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{id}", name="admin_revue_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_REVUES')")
     */
    public function edit(Request $request, Revue $revue): Response
    {
        $em = $this->getDoctrine()->getManager(); 

        $form = $this->createForm(RevueType::class, $revue);
        $form->handleRequest($request);

        $old_clients = $revue->getClients();
        if ($old_clients) {
            foreach($old_clients as $old_client) {
                $old_client->removeRevue($revue);
                $em->persist($old_client);
            }
            $em->flush();
        }

        if ($form->isSubmitted() && $form->isValid()) {                    
            try {                
                $revue->setUpdateUser($this->getUser());
                foreach ($revue->getClients() as $client) {
                    $client->addRevue($revue);
                    $em->persist($client);
                } 

                $em->persist($revue);
                $em->flush();

                $this->addFlash('success', $this->translator->trans('revue.flash.updated'));

                return $this->redirectToRoute('admin_revue_show', ['id' => $revue->getId()]);
            } catch (DBALException $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (\Throwable $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('RevueManagement/Revue/edit.html.twig', [
            'revue' => $revue,
            'form' => $form->createView(),
        ]);
    }

    /**                           
     * @Route("/show/{id}", name="admin_revue_show", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_REVUES') or is_granted('ROLE_GESTION_NUMEROS')")
     */
    public function show(Request $request, Revue $revue): Response
    {
        $form = $this->createForm(RevueDeleteType::class, $revue);

        return $this->render('RevueManagement/Revue/show.html.twig', [
            'revues' => $this->revueRepository->findBy(['isDeleted' => false], ['title' => 'DESC']),
            'revue' => $revue,
            'form' => $form->createView(),
            'action' => $request->get('action'),            
        ]);
    }

    /**
     * Desactive/Active un revue.
     *
     * @Route("/disable/{id}", name="admin_revue_disable", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_REVUES')")
     */
    public function disable(Revue $revue)
    {
        $em = $this->getDoctrine()->getManager();

        if ($revue->getIsValid()) {
            $revue->setIsValid(false);
            $this->addFlash('success', $this->translator->trans('revue.flash.disable'));
        } else {
            $revue->setIsValid(true);
            $this->addFlash('success', $this->translator->trans('revue.flash.enable'));
        }

        try {
            $revue->setUpdateUser($this->getUser());
            $em->persist($revue);
            $em->flush();
        } catch (DBALException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_revue_index');
    }

    /**
     * not_used
     * @Route("/_clients/{id}", name="admin_revue_clients", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_LIEN_REVUES_CLIENTS')")
     */
    public function _clients(Request $request, Revue $revue): Response
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm('App\Form\RevueManagement\_RevueClientType', $revue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {        
            $revue->setUpdateUser($this->getUser());
            $em->persist($revue);
            $em->flush();
            
            $this->addFlash('success',  $this->translator->trans('revue.flash.clients_updated'));
        }

        return $this->render('RevueManagement/Revue/__revue_clients.html.twig', [
            'form' => $form->createView(),
            'clients' => $em->getRepository('App\Entity\UserManagement\Client')->findBy(['isValid' => true], ['name' => 'DESC']),
            'oldClients' => $em->getRepository('App\Entity\UserManagement\Client')->findByRevue($revue->getId()),          
            'revue' => $revue,
        ]);
    }
}
