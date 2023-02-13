<?php

namespace App\Controller\UserManagement;

use App\Entity\UserManagement\Client;
use App\Form\UserManagement\ClientEditType;
use App\Form\UserManagement\ClientDeleteType;
use App\Form\UserManagement\ClientType;
use App\Message\UserManagement\ClientLogoCreatedOrUpdated;
use App\Repository\UserManagement\ClientRepository;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/admin/clients")
 */
class ClientController extends AbstractController
{
    private $kernel;
    private $messageBus;
    private $slugger;
    private $translator;

    public function __construct(MessageBusInterface $messageBus, TranslatorInterface $translator, KernelInterface $kernel, SluggerInterface $slugger)
    {
        $this->kernel = $kernel;
        $this->messageBus = $messageBus;
        $this->slugger = $slugger;
        $this->translator = $translator;
    }

    /**
     * @Route("/index", name="admin_client_index", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_CLIENTS')")
     */
    public function index(ClientRepository $clientRepository): Response
    {
        return $this->render('UserManagement/Client/index.html.twig', [
            'clients' => $clientRepository->findBy(['isDeleted' => false], ['updateDate' => 'DESC']),
        ]);
    }

    /**
     * @Route("/new", name="admin_client_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_CLIENTS')")
     */
    public function new(Request $request): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            try {
                $client->setCreateUser($this->getUser());
                $client->setUpdateUser($this->getUser());

                $entityManager->persist($client);
                $entityManager->flush();

                //$this->messageBus->dispatch(new ClientLogoCreatedOrUpdated($client));
                $this->addFlash('success', $this->translator->trans('client.flash.created'));

                return $this->redirectToRoute('admin_client_show', ['id'=>$client->getId()]);
            } catch (DBALException $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (\Throwable $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('UserManagement/Client/new.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/show/{id}", name="admin_client_show", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_CLIENTS')")
     */
    public function show(Request $request, Client $client): Response
    {
        $form = $this->createForm(ClientDeleteType::class, $client);

        return $this->render('UserManagement/Client/show.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
            'action' => $request->get('action'),            
        ]);
    }

    /**
     * @Route("/edit/{id}", name="admin_client_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_GESTION_CLIENTS')")
     */
    public function edit(Request $request, Client $client): Response
    {
        $form = $this->createForm(ClientType::class, $client);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $client->setUpdateUser($this->getUser());
            // maybe late, it wake up
            // $uploadFile = $form->get('logo')->getData();
            // if ($uploadFile) {
            //     $originalFilename = pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME);

            //     $safeFilename = $this->slugger->slug($originalFilename);
            //     $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadFile->guessExtension();

            //     try {
            //         $uploadFile->move($this->kernel->getProjectDir() .'/data/', $newFilename);
            //     } catch (FileException $e) {
            //         $this->addFlash('error', $e->getMessage());
            //     }
            //     $client->setLogoUri($newFilename);
            // }

            try {
                $em->persist($client);
                $em->flush();

                // if ($uploadFile) {
                //     $this->messageBus->dispatch(new ClientLogoCreatedOrUpdated($client));
                // }

                $this->addFlash('success', $this->translator->trans('client.flash.updated'));

                return $this->redirectToRoute('admin_client_show', ['id'=>$client->getId()]);
            } catch (DBALException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('UserManagement/Client/edit.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Desactive/Active un client.
     *
     * @Route("/disable/{id}", name="admin_client_disable", methods={"POST"})
     * @Security("is_granted('ROLE_GESTION_CLIENTS')")
     */
    public function disable(Client $client)
    {
        $em = $this->getDoctrine()->getManager();

        if ($client->getIsValid()) {
            $client->setIsValid(false);
            $this->addFlash('success', $this->translator->trans('client.flash.disable'));
        } else {
            $client->setIsValid(true);
            $this->addFlash('success', $this->translator->trans('client.flash.enable'));
        }

        try {
            $client->setUpdateUser($this->getUser());
            $em->persist($client);
            $em->flush();
        } catch (DBALException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_client_show', ['id' => $client->getId()]);
    }

    /**
     * @Route("/delete/{id}", name="admin_client_delete", methods={"POST"})
     * @Security("is_granted('ROLE_GESTION_CLIENTS')")
     */
    public function delete(Request $request, Client $client): Response
    {
        $form = $this->createForm(ClientDeleteType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            try {
                $client->setUpdateUser($this->getUser());     
                $client->setIsDeleted(true);                           
                $em->persist($client);
                $em->flush();

                $this->addFlash('success', $this->translator->trans('client.flash.deleted'));
                return $this->redirectToRoute('admin_client_show', ['id' => $client->getId()]);

            } catch (\Doctrine\DBAL\DBALException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->redirectToRoute('admin_client_show', ['id' => $client->getId(), 'action'=>'delete']);
    }

    /**
     * @Route("/dcis/{id}", name="admin_client_dcis", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_LIEN_MOTCLE_CLIENTS')")
     */
    public function dcis(Request $request, Client $client): Response
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm('App\Form\UserManagement\ClientDciType', $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $client->setUpdateUser($this->getUser());
            $em->persist($client);
            $em->flush();
            
            $this->addFlash('success',  $this->translator->trans('client.flash.dcis_updated'));
        }

        return $this->render('UserManagement/Client/client_dcis.html.twig', [
            'form' => $form->createView(),
            'dcis' => $em->getRepository('App\Entity\SearchManagement\Dci')->findBy(['isValid' => true], ['title' => 'DESC']),
            'oldDcis' => $em->getRepository('App\Entity\SearchManagement\Dci')->findByClient($client->getId()),
            'client' => $client,
        ]);
    }

    /**
     * @Route("/revues/{id}", name="admin_client_revues", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_LIEN_REVUES_CLIENTS')")
     */
    public function revues(Request $request, Client $client): Response
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm('App\Form\UserManagement\ClientRevueType', $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $client->setUpdateUser($this->getUser());
            $em->persist($client);
            $em->flush();
            
            $this->addFlash('success',  $this->translator->trans('client.flash.revues_updated'));
        }

        return $this->render('UserManagement/Client/client_revues.html.twig', [
            'form' => $form->createView(),
            'revues' => $em->getRepository('App\Entity\RevueManagement\Revue')->findBy(['isValid' => true], ['title' => 'DESC']),
            'oldRevues' => $em->getRepository('App\Entity\RevueManagement\Revue')->findByClient($client->getId()),          
            'client' => $client,
        ]);
    }    
}
