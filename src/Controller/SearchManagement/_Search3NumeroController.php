<?php

namespace App\Controller\SearchManagement;

use App\Entity\RevueManagement\Image;
use App\Entity\RevueManagement\Numero;
use App\Entity\SearchManagement\Dci;
use App\Repository\RevueManagement\BlockRepository;
use App\Repository\RevueManagement\ImageRepository;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\RevueManagement\PageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/admin/search3")
 * @codeCoverageIgnore
 */
class _Search3NumeroController extends AbstractController
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
     * Search in a specific number journal;.
     *
     * @Route("/numero3/{id}", name="admin_search3_numero3", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function searchRevueNumber3(Request $request, Numero $number)
    {
        $clients = $number->getRevue()->getClients();

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
                //admin_search3_numero_dci
                return $this->redirectToRoute('admin_search3_numero_dci', ['numero' => $number->getId(), 'dci' => $data['dci']]);
            }
        }

        return $this->render('SearchManagement/Result/revue_number3.html.twig', [
            'number' => $number,
            'clients' => $clients,
            'mysqlFullTextSearch' => true,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Search in a specific number journal(image) in a specific page.
     *
     * @Route("/numero/image/{numero}/{dci}/{input}", name="admin_search3_image_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function searchRevueNumberImage(Request $request, Numero $numero, string $dci, string $input): Response
    {
        $scroll = ($request->get('scroll')) ? 'overflow-y:scroll' : '';
        $no_position = ($request->get('no_position')) ? true : false;

        $ext = explode('-', $input);
        $image = $this->imageRepository->findOneBy(['id' => $ext[0]]);

        if (!$image) {
            throw $this->createNotFoundException();
        }
        $_blocks = [];
        foreach ($image->getPages() as $page) {
            foreach ($page->getBlocks() as $block) {
                $_blocks[] = $block;
            }
        }

        return $this->render('SearchManagement/Result/revue_number_image3.html.twig', [
            'numero' => $numero,
            'dci' => $dci,
            'image' => $image,
            'blocks' => $_blocks,
            'result' => $input,
            'mysqlFullTextSearch' => true,
            'scroll' => $scroll,
            'no_position' => $no_position,
        ]);
    }

    /**
     * Search in a specific number journal(pdf) in a specific page.
     *
     * @Route("/numero/pdf/{numero}/{dci}/{page}", name="admin_search3_pdf_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function searchRevueNumberPdf(Request $request, Numero $numero, string $dci, int $page): Response
    {
        $scroll = ($request->get('scroll')) ? 'overflow-y:scroll' : '';
        $no_position = ($request->get('no_position')) ? true : false;

        $image = $this->imageRepository->findOneBy(['numero' => $numero, 'numeroPage' => $page]);

        if (!$image) {
            throw $this->createNotFoundException();
        }
        $_blocks = [];
        foreach ($image->getPages() as $_page) {
            foreach ($_page->getBlocks() as $block) {
                $_blocks[] = $block;
            }
        }

        return $this->render('SearchManagement/Result/revue_number_pdf3.html.twig', [
            'numero' => $numero,
            'dci' => $dci,
            'image' => $image,
            'blocks' => $_blocks,
            'page' => $page,
            'mysqlFullTextSearch' => true,
            'scroll' => $scroll,
            'no_position' => $no_position,
        ]);
    }

    /**
     * Search in a specific number journal with a dic.
     *
     * @Route("/numero/dci/{numero}/{dci}", name="admin_search3_numero_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function searchRevueNumberDci(Request $request, Numero $numero, string $dci)
    {
        $images = $this->imageRepository->findBy(['numero' => $numero, 'isDeleted' => false], ['numeroPage' => 'ASC']);

        $array_search = [];

        foreach ($images as $image) {
            $pages = $this->pageRepository->findBy(['image' => $image, 'isDeleted' => false], ['numeroPage' => 'ASC']);
            foreach ($pages as $page) {
                foreach ($page->getBlocks() as $block) {
                    if ($this->blockRepository->findDciByText($block, $dci)) {
                        if (!isset($array_search[$image->getNumeroPage()])) {
                            $array_search[$image->getNumeroPage()] = [];
                        }
                        array_push($array_search[$image->getNumeroPage()], $block->getText());
                    }
                }
            }
        }

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
                //admin_search3_numero_dci
                return $this->redirectToRoute('admin_search3_numero_dci', ['numero' => $numero->getId(), 'dci' => $data['dci']]);
            }
        }

        return $this->render('SearchManagement/Result/revue_number_dci.html.twig', [
            'array_search' => $array_search,
            'dci' => urldecode($dci),
            'numero' => $numero,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Search in a specific number journal with a dic.
     *
     * @Route("/numero/idDci/{numero}/{dci}", name="admin_search3_numero_idDci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function searchRevueNumberByDciId(Request $request, Numero $numero, Dci $dci)
    {
        $images = $this->imageRepository->findBy(['numero' => $numero, 'isDeleted' => false], ['numeroPage' => 'ASC']);

        $array_search = [];

        foreach ($images as $image) {
            $pages = $this->pageRepository->findBy(['image' => $image, 'isDeleted' => false], ['numeroPage' => 'ASC']);
            foreach ($pages as $page) {
                foreach ($page->getBlocks() as $block) {
                    if ($this->blockRepository->findDciByText($block, $dci->getTitle())) {
                        if (!isset($array_search[$image->getNumeroPage()])) {
                            $array_search[$image->getNumeroPage()] = [];
                        }
                        array_push($array_search[$image->getNumeroPage()], $block->getText());
                    }
                }
            }
        }

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
                //admin_search3_numero_dci
                return $this->redirectToRoute('admin_search3_numero_dci', ['numero' => $numero->getId(), 'dci' => $data['dci']]);
            }
        }

        return $this->render('SearchManagement/Result/revue_number_dci.html.twig', [
            'array_search' => $array_search,
            'dci' => $dci->getTitle(),
            'numero' => $numero,
            'form' => $form->createView(),
        ]);
    }
}
