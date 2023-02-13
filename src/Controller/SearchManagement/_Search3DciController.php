<?php

namespace App\Controller\SearchManagement;

use App\Entity\SearchManagement\Dci;
use App\Repository\RevueManagement\BlockRepository;
use App\Repository\RevueManagement\ImageRepository;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\RevueManagement\PageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @codeCoverageIgnore
 * @Route("/admin/search3")
 */
class _Search3DciController extends AbstractController
{
    private $translator;
    private $blockRepository;
    private $imageRepository;
    private $pageRepository;
    private $numeroRepository;

    public function __construct(BlockRepository $blockRepository, ImageRepository $imageRepository, PageRepository $pageRepository, NumeroRepository $numeroRepository, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->blockRepository = $blockRepository;
        $this->imageRepository = $imageRepository;
        $this->pageRepository = $pageRepository;
        $this->numeroRepository = $numeroRepository;
    }

    /**
     * Search in a specific dci by dci string, V2.
     *
     * @Route("/dci/{dci}", name="admin_search3_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     * for dev
     */
    public function searchDci(Request $request, string $dci)
    {
        $defaultData = ['dci' => $dci];
        $form = $this->createFormBuilder($defaultData)
            ->add('dci', TextType::class, ['required' => true])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data['dci'])) {
                return $this->redirectToRoute('admin_search3_dci', ['dci' => $data['dci']]);
            }
        }

        $em = $this->getDoctrine()->getManager();

        $blocks = $this->blockRepository->findAllByDci($dci);

        //dd($blocks);

        return $this->render('SearchManagement/Result/revue_dci2.html.twig', [
            'blocks' => $blocks,
            'dci' => $dci,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Search in a specific dci by dci_id, V2.
     *
     * @Route("/idDci/{dci}", name="admin_search3_idDci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     * for dev
     */
    public function searchDciById(Request $request, Dci $dci)
    {
        $defaultData = ['dci' => $dci->getTitle()];
        $form = $this->createFormBuilder($defaultData)
            ->add('dci', TextType::class, ['required' => true])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data['dci'])) {
                return $this->redirectToRoute('admin_search3_dci', ['dci' => $data['dci']]);
            }
        }

        $em = $this->getDoctrine()->getManager();

        $blocks = $this->blockRepository->findAllByDci($dci->getTitle());

        return $this->render('SearchManagement/Result/revue_dci2.html.twig', [
            'blocks' => $blocks,
            'dci' => $dci->getTitle(),
            'form' => $form->createView(),
        ]);
    }
}
