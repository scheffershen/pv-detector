<?php

namespace App\Controller\SearchManagement;

use App\Entity\RevueManagement\Block;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use whatwedo\SearchBundle\Entity\Index;

/**
 * @codeCoverageIgnore
 * @Route("/admin/search2")
 * for dev, useless
 */
class _Search2Controller extends AbstractController
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
     * @Route("/{id}", name="admin_search2_dci", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function searchRevueNumber(Numero $number)
    {
        $em = $this->getDoctrine()->getManager();

        $clients = $number->getRevue()->getClients();
        $array_search = [];

        if ($number->isPdf()) {
            $pages = $this->pageRepository->findBy(['numero' => $number, 'isDeleted' => false], ['numeroPage' => 'ASC']);
            foreach ($clients as $client) {
                $array_search[$client->getName()] = [];
                $dcis = $client->getDcis();
                foreach ($dcis as $dci) {
                    $array_search[$client->getName()][$dci->getTitle()] = [];
                    foreach ($pages as $page) {
                        if (false !== mb_stripos($page->getContent(), $dci->getTitle()) || false !== mb_stripos($page->getContent(), mb_strtolower($dci->getTitle())) || false !== mb_stripos($page->getContent(), mb_strtoupper($dci->getTitle())) || false !== mb_stripos($page->getContent(), Utils::no_accent($dci->getTitle()))) {
                            array_push($array_search[$client->getName()][$dci->getTitle()], $page->getNumeroPage());
                        }
                    }
                }
            }
        } else {
            foreach ($number->getImages() as $image) {
                foreach ($clients as $client) {
                    $array_search[$client->getName()] = [];
                    $dcis = $client->getDcis();
                    foreach ($dcis as $dci) {
                        $array_search[$client->getName()][$dci->getTitle()] = [];
                        // Get all id's of entities containing the query string
                        $ids = $em->getRepository(Index::class)->search($dci->getTitle(), Block::class);
                        // Map blocks
                        $blocks = $em->getRepository(Block::class)->createQueryBuilder('b')->where('b.id IN (:ids)')->setParameter('ids', $ids)->getQuery()->getResult();

                        foreach ($image->getPages() as $page) {
                            foreach ($page->getBlocks() as $block) {
                                foreach ($blocks as $b) {
                                    if ($b === $block) {
                                        array_push($array_search[$client->getName()][$dci->getTitle()], $image->getId().'-'.$image->getNumeroPage());
                                        break 2;
                                    }
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
            //"url_revue" => $url,
        ]);
    }

    /**
     * Search in a specific number journal.
     *
     * @Route("/image/{numero}/{dci}/{input}", name="admin_search2_image_dci", methods={"GET","POST"})
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
        // Get all id's of entities containing the query string
        $ids = $em->getRepository(Index::class)->search($dci, Block::class);
        // Map blocks
        $blocks = $em->getRepository(Block::class)->createQueryBuilder('b')->where('b.id IN (:ids)')->setParameter('ids', $ids)->getQuery()->getResult();
        foreach ($image->getPages() as $page) {
            foreach ($page->getBlocks() as $block) {
                foreach ($blocks as $b) {
                    if ($b === $block) {
                        $_blocks[] = $block;
                    }
                }
            }
        }

        return $this->render('SearchManagement/Result/revue_number_image.html.twig', [
            'numero' => $numero,
            'dci' => $dci,
            'image' => $image,
            'blocks' => $_blocks,
        ]);
    }
}
