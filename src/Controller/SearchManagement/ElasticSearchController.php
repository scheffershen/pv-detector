<?php

namespace App\Controller\SearchManagement;

use Elastica\Util;
use FOS\ElasticaBundle\Finder\TransformedFinder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Rap2hpoutre\RemoveStopWords\remove_stop_words;

/**
 * @Route("/admin/search4")
 */
class ElasticSearchController extends AbstractController
{
    /**
     * @Route("/dci/{dci}", name="admin_search4_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function searchAction(Request $request, TransformedFinder $pagesFinder, string $dci): Response
    {
        $dci = urldecode($dci);

        $defaultData = ['dci' => $dci];
        $form = $this->createFormBuilder($defaultData)
            ->add('dci', TextType::class, ['required' => true])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data['dci'])) {
                return $this->redirectToRoute('admin_search4_dci', ['dci' => $data['dci']]);
            }
        }

        $dci = remove_stop_words($dci, 'fr');
        $dci = remove_stop_words($dci, 'en');
        
        $search = Util::escapeTerm($dci);

        $results = $pagesFinder->findHybrid($search, 100);

        return $this->render('SearchManagement/Result/search4_dci.html.twig', [
            'results' => $results,
            'dci' => $dci,
            'form' => $form->createView(),
        ]);
    }
}
