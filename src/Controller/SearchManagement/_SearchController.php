<?php

namespace App\Controller\SearchManagement;

use App\Entity\RevueManagement\Image;
use App\Entity\RevueManagement\Numero;
use App\Entity\RevueManagement\Revue;
use App\Entity\SearchManagement\Dci;
use App\Repository\RevueManagement\BlockRepository;
use App\Repository\RevueManagement\ImageRepository;
use App\Repository\RevueManagement\PageRepository;
use App\Utils\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @codeCoverageIgnore
 * @Route("/admin/search")
 * old version
 */
class _SearchController extends AbstractController
{
    private $translator;
    private $blockRepository;
    private $imageRepository;
    private $pageRepository;

    public function __construct(BlockRepository $blockRepository, ImageRepository $imageRepository, PageRepository $pageRepository, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->blockRepository = $blockRepository;
        $this->imageRepository = $imageRepository;
        $this->pageRepository = $pageRepository;
    }

    /**
     * Search in a specific number journal.
     *
     * @Route("/{id}", name="admin_search_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function searchRevueNumber(Numero $number)
    {
        $em = $this->getDoctrine()->getManager();
        //$url = $this->params->get('revue_url').$this->generateUrl('admin_private_upload', array('upload' => $number->getFileUri(), 'format' => 'revues'));

        $clients = $number->getRevue()->getClients();
        //dd(count($clients));
        //dd($number->isPdf());
        $array_search = [];

        if ($number->isPdf()) {
            $pages = $this->pageRepository->findBy(['numero' => $number, 'isDeleted' => false], ['numeroPage' => 'ASC']);
            foreach ($clients as $client) {
                $array_search[$client->getName()] = [];
                $dcis = $client->getDcis();
                foreach ($dcis as $dci) {
                    $array_search[$client->getName()][$dci->getTitle()] = [];
                    foreach ($pages as $page) {
                        /*
                        if($page->getNumeroPage() == 34 ){
                            echo $page->getNumeroPage()." : ".$dci->getTitle()." : ".mb_strtolower($dci->getTitle())." : ".mb_strtoupper($dci->getTitle())." : ".self::no_accent($dci->getTitle());
                            echo "<br>";
                            echo $page->getContent();
                            echo "<br>";
                            var_dump(mb_stripos($page->getContent(), $dci->getTitle()));
                            var_dump(mb_stripos($page->getContent(), mb_strtolower($dci->getTitle())));
                            var_dump(mb_stripos($page->getContent(), mb_strtoupper($dci->getTitle())));
                            var_dump(mb_stripos($page->getContent(), self::no_accent($dci->getTitle())));
                        }
                        */
                        if (false !== mb_stripos($page->getContent(), $dci->getTitle()) || false !== mb_stripos($page->getContent(), mb_strtolower($dci->getTitle())) || false !== mb_stripos($page->getContent(), mb_strtoupper($dci->getTitle())) || false !== mb_stripos($page->getContent(), Utils::no_accent($dci->getTitle()))) {
                            array_push($array_search[$client->getName()][$dci->getTitle()], $page->getNumeroPage());
                        }
                    }
                }
            }
            // exit;
        } else {
            foreach ($clients as $client) {
                $array_search[$client->getName()] = [];
                $dcis = $client->getDcis();
                foreach ($dcis as $dci) {
                    $array_search[$client->getName()][$dci->getTitle()] = [];
                    foreach ($number->getImages() as $image) {
                        foreach ($image->getPages() as $page) {
                            //$array_search[$client->getName()][$dci->getTitle()][$image->getId()] = [];
                            foreach ($page->getBlocks() as $block) {
                                if (false !== mb_stripos($block->getText(), $dci->getTitle()) || false !== mb_stripos($block->getText(), mb_strtolower($dci->getTitle())) || false !== mb_stripos($block->getText(), mb_strtoupper($dci->getTitle())) || false !== mb_stripos($block->getText(), Utils::no_accent($dci->getTitle()))) {
                                    array_push($array_search[$client->getName()][$dci->getTitle()], $image->getId().'-'.$image->getNumeroPage());
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->render('SearchManagement/Result/revue_number.html.twig', [
            'array_search' => $array_search,
            'number' => $number,
            'mysqlFullTextSearch' => false,
        ]);
    }

    /**
     * Search in a specific number journal.
     *
     * @Route("/image/{numero}/{dci}/{input}", name="admin_search_image_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function searchRevueNumberImage(Numero $numero, string $dci, string $input): Response
    {
        $em = $this->getDoctrine()->getManager();

        $ext = explode('-', $input);
        $image = $this->imageRepository->findOneBy(['id' => $ext[0]]);

        if (!$image) {
            throw $this->createNotFoundException();
        }
        $_blocks = [];
        foreach ($image->getPages() as $page) {
            foreach ($page->getBlocks() as $block) {
                if (false !== mb_stripos($block->getText(), $dci)) {
                    $block->setText(str_replace($dci, "<span class='badge badge-warning'>".$dci.'</span>', $block->getText()));
                    $_blocks[] = $block;
                } elseif (false !== mb_stripos($block->getText(), mb_strtolower($dci))) {
                    $block->setText(str_replace(mb_strtolower($dci), "<span class='badge badge-warning'>".mb_strtolower($dci).'</span>', $block->getText()));
                    $_blocks[] = $block;
                } elseif (false !== mb_stripos($block->getText(), mb_strtoupper($dci))) {
                    $block->setText(str_replace(mb_strtoupper($dci), "<span class='badge badge-warning'>".mb_strtoupper($dci).'</span>', $block->getText()));
                    $_blocks[] = $block;
                } elseif (false !== mb_stripos($block->getText(), Utils::no_accent($dci))) {
                    $block->setText(str_replace(Utils::no_accent($dci), "<span class='badge badge-warning'>".Utils::no_accent($dci).'</span>', $block->getText()));
                    $_blocks[] = $block;
                } else {
                    $_blocks[] = $block;
                }
            }
        }

        return $this->render('SearchManagement/Result/revue_number_image.html.twig', [
            'numero' => $numero,
            'dci' => $dci,
            'image' => $image,
            'blocks' => $_blocks,
            'result' => $input,
            'mysqlFullTextSearch' => false,
        ]);
    }

    /**
     * Search in a specific number journal.
     *
     * @Route("/pdf/{numero}/{dci}/{page}", name="admin_search_pdf_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function searchRevueNumberPdf(Request $request, Numero $numero, string $dci, int $page): Response
    {
        $em = $this->getDoctrine()->getManager();

        $image = $this->imageRepository->findOneBy(['numero' => $numero, 'numeroPage' => $page]);

        if (!$image) {
            throw $this->createNotFoundException();
        }
        $_blocks = [];
        foreach ($image->getPages() as $_page) {
            foreach ($_page->getBlocks() as $block) {
                if (false !== mb_stripos($block->getText(), $dci)) {
                    $block->setText(str_replace($dci, "<span class='badge badge-warning'>".$dci.'</span>', $block->getText()));
                    $_blocks[] = $block;
                } elseif (false !== mb_stripos($block->getText(), mb_strtolower($dci))) {
                    $block->setText(str_replace(mb_strtolower($dci), "<span class='badge badge-warning'>".mb_strtolower($dci).'</span>', $block->getText()));
                    $_blocks[] = $block;
                } elseif (false !== mb_stripos($block->getText(), mb_strtoupper($dci))) {
                    $block->setText(str_replace(mb_strtoupper($dci), "<span class='badge badge-warning'>".mb_strtoupper($dci).'</span>', $block->getText()));
                    $_blocks[] = $block;
                } elseif (false !== mb_stripos($block->getText(), Utils::no_accent($dci))) {
                    $block->setText(str_replace(Utils::no_accent($dci), "<span class='badge badge-warning'>".Utils::no_accent($dci).'</span>', $block->getText()));
                    $_blocks[] = $block;
                } else {
                    $_blocks[] = $block;
                }
            }
        }

        return $this->render('SearchManagement/Result/revue_number_pdf.html.twig', [
            'numero' => $numero,
            'dci' => $dci,
            'image' => $image,
            'blocks' => $_blocks,
            'page' => $page,
            'mysqlFullTextSearch' => false,
        ]);
    }
}
