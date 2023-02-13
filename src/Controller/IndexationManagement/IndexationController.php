<?php

namespace App\Controller\IndexationManagement;

use App\Entity\RevueManagement\Numero;
use App\Entity\RevueManagement\Revue;
use App\Entity\SearchManagement\Dci;
use App\Entity\UserManagement\Client;
use App\Repository\SearchManagement\IndexationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Environment;
use function Rap2hpoutre\RemoveStopWords\remove_stop_words;
//use StopWords\StopWords;

/**
 * @Route("/admin/indexation2")
 */
class IndexationController extends AbstractController
{
    private $indexationRepository;
    private $twig;
    private $slugger;

    public function __construct(IndexationRepository $indexationRepository, SluggerInterface $slugger, Environment $twig)
    {
        $this->indexationRepository = $indexationRepository;
        $this->twig = $twig;
        $this->slugger = $slugger;
    }

    /**
     * @Route("/numero/{numero}", name="admin_indexation_numero", methods={"GET"})
     * @Security("is_granted('ROLE_REPRESENTATION_RESULTATS')")
     * 
     * cas 1: touts_les_indexations 
     * cas 2: clients_de_la_revue
     * cas 3: un client_de_la_revue
     */
    public function numero(Request $request, Numero $numero): Response
    {
        $indexations = $this->indexationRepository->findByNumero2($numero);
        
        return $this->render('IndexationManagement/numero.html.twig', [
            'numero' => $numero,
            'indexations' => $indexations,
            'action' => $request->get('action')
        ]);
    }

    /**
     * @Route("/numero_dci/{numero}/{dci}", name="admin_indexation_numero_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_REPRESENTATION_RESULTATS')")
     */
    public function numeroDci(Numero $numero, Dci $dci): Response
    {
        $indexations = $this->indexationRepository->findByNumeroDci($numero, $dci);

        return $this->render('IndexationManagement/numero_dci.html.twig', [
            'numero' => $numero,
            'dci' => $dci,
            'indexations' => $indexations,
        ]);
    }

    /**
     * @Route("/dci/{dci}", name="admin_indexation_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_REPRESENTATION_RESULTATS')")
     */
    public function dci(Dci $dci): Response
    {
        $indexations = $this->indexationRepository->findByDci($dci);

        return $this->render('IndexationManagement/dci.html.twig', [
            'dci' => $dci,
            'indexations' => $indexations,
        ]);
    }

    /**
     * @Route("/numeroExport/{numero}", name="admin_indexation_numero_export", methods={"GET"})
     * @Security("is_granted('ROLE_REPRESENTATION_RESULTATS')")
     */
    public function numeroExport(Numero $numero): Response
    {
        $indexations = $this->indexationRepository->findByNumero2($numero);

        $content = [];

        $content = $this->twig->render('IndexationManagement/numero_excel.html.twig', [
            'numero' => $numero,
            'indexations' => $indexations]);

        return new Response($content, 200, [
            'Content-Type' => 'application/force-download;charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->slugger->slug($numero->getTitle()).'_indexation_'.(new \Datetime())->format('Y-m-d H:i:s').'.xls"',
        ]);        
    }


    /**
     * @Route("/client/{client}", name="admin_indexation_client", methods={"GET","POST"})
     * @Security("is_granted('ROLE_REPRESENTATION_RESULTATS')")
     */
    public function client(Client $client): Response
    {
        return $this->render('IndexationManagement/client.html.twig', [
            'client' => $client,
        ]);
    } 
}
