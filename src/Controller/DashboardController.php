<?php

namespace App\Controller;

use App\Repository\RapportManagement\RapportRepository;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\UserManagement\ClientRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    private $clientRepository;
    private $numeroRepository;
    private $rapportRepository;

    public function __construct(ClientRepository $clientRepository, NumeroRepository $numeroRepository, RapportRepository $rapportRepository)
    {
        $this->clientRepository = $clientRepository;
        $this->numeroRepository = $numeroRepository;
        $this->rapportRepository = $rapportRepository;
    }

    /**
     * admin dashboard v2
     *
     * @Route("/admin/dashboard", name="admin_dashboard", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function dashboard(Request $request): Response
    {
        // 10 dernières numéros sans rapport && Mes rapports clients
        if (\in_array('ROLE_CLIENT', $this->getUser()->getRoles())) {
            if ( \count($this->getUser()->getClients()) > 0 ) {
                $numeros_avec_rapport = $this->numeroRepository->numerosSansOrAvecRapport($this->getUser()->getClients());
            } else {
                $numeros_avec_rapport = null;
            }
            
            return $this->render('Dashboard/client_dashboard.html.twig', [
                'numeros_avec_rapport' => $numeros_avec_rapport,
            ]);

        } 

        // for another user (admin, gestionnaire et lecteur)
        return $this->render('Dashboard/admin_dashboard.html.twig', [
            'numeros_sans_rapport' => $this->numeroRepository->numerosSansOrAvecRapport(),
            'mes_rapports_clients' => $this->clientRepository->mesRapportClients($this->getUser()),
        ]);
    }

    /**
     * admin dashboard v1, userless
     *
     * @Route("/admin/_dashboard", name="_admin_dashboard", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function _dashboard(Request $request): Response
    {
        $form = $this->createFormBuilder()
            ->add('dci', TextType::class, [
                'attr' => [
                    'placeholder' => 'Type your dci here',
                ],
                'required' => true, ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data['dci'])) {
                //admin_search3_dci
                return $this->redirectToRoute('admin_search4_dci', ['dci' => $data['dci']]);
            }
        }

        return $this->render('Dashboard/_admin_dashboard.html.twig', [
            'clients' => $this->clientRepository->findBy(['isValid' => true], ['updateDate' => 'DESC'], 1, 0),
            'numeros' => $this->numeroRepository->findBy(['isValid' => true], ['receiptDate' => 'DESC'], 10, 0),
            'form' => $form->createView(),
        ]);
    }
}
