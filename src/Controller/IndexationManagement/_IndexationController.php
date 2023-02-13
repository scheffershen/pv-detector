<?php

namespace App\Controller\IndexationManagement;

use App\Entity\RevueManagement\Numero;
use App\Entity\RevueManagement\Revue;
use App\Entity\SearchManagement\Dci;
use App\Entity\UserManagement\Client;
use App\Repository\SearchManagement\IndexationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/_indexation")
 */
class _IndexationController extends AbstractController
{
    private $indexationRepository;

    public function __construct(IndexationRepository $indexationRepository)
    {
        $this->indexationRepository = $indexationRepository;
    }

    /**
     * @Route("/revue/{revue}", name="_admin_indexation_revue", methods={"GET","POST"})
     * @Security("is_granted('ROLE_REPRESENTATION_RESULTATS')")
     */
    public function revue(Revue $revue): Response
    {
        return $this->render('_IndexationManagement/revue.html.twig', [
            'controller_name' => 'ResultController',
        ]);
    }

    /**
     * @Route("/numero/{numero}", name="_admin_indexation_numero", methods={"GET","POST"})
     * @Security("is_granted('ROLE_REPRESENTATION_RESULTATS')")
     */
    public function numero(Numero $numero): Response
    {
        $indexations = $this->indexationRepository->findByNumero($numero);

        return $this->render('_IndexationManagement/numero.html.twig', [
            'numero' => $numero,
            'indexations' => $indexations,
        ]);
    }

    /**
     * @Route("/numero_dci/{numero}/{dci}", name="_admin_indexation_numero_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_REPRESENTATION_RESULTATS')")
     */
    public function numeroDci(Numero $numero, Dci $dci): Response
    {
        $indexations = $this->indexationRepository->findByNumeroDci($numero, $dci);

        return $this->render('_IndexationManagement/numero_dci.html.twig', [
            'numero' => $numero,
            'dci' => $dci,
            'indexations' => $indexations,
        ]);
    }

    /**
     * @Route("/dci/{dci}", name="_admin_indexation_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_REPRESENTATION_RESULTATS')")
     */
    public function dci(Dci $dci): Response
    {
        $indexations = $this->indexationRepository->findByDci($dci);

        return $this->render('_IndexationManagement/dci.html.twig', [
            'dci' => $dci,
            'indexations' => $indexations,
        ]);
    }

    /**
     * @codeCoverageIgnore
     * @Route("/_client/{client}", name="_admin_indexation_client", methods={"GET","POST"})
     * @Security("is_granted('ROLE_REPRESENTATION_RESULTATS')")
     */
    public function _client(Client $client): Response
    {
        $indexations = $this->indexationRepository->findByClient($client);

        $new_indexations = [];
        foreach ($indexations as $indexation) {
            $existed = false;
            for ($i = 0; $i < \count($new_indexations); ++$i) {
                if ($new_indexations[$i]['dci'] == $indexation['dci'] && $new_indexations[$i]['numero_id'] == $indexation['numero_id']) {
                    $existed = true;
                    ++$new_indexations[$i]['count_numero'];
                    break;
                }
            }
            $k = \count($new_indexations);
            if (!$existed) {
                $new_indexations[$k]['dci'] = $indexation['dci'];
                $new_indexations[$k]['revue'] = $indexation['revue'];
                $new_indexations[$k]['numero_id'] = $indexation['numero_id'];
                $new_indexations[$k]['numero'] = $indexation['numero'];
                $new_indexations[$k]['receiptDate'] = $indexation['receiptDate'];
                $new_indexations[$k]['count_numero'] = 1;
            }
        }

        return $this->render('_IndexationManagement/_client.html.twig', [
            'client' => $client,
            'indexations' => $new_indexations,
        ]);
    }
}
