<?php

namespace App\Manager\RevueManagement;

use App\Entity\RevueManagement\Image;
use App\Entity\RevueManagement\Numero;
use App\Entity\RevueManagement\Page;
use App\Repository\RevueManagement\ImageRepository;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\RevueManagement\PageRepository;
use App\Utils\Utils;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Smalot\PdfParser\Parser;
use Spatie\PdfToImage\Pdf;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class NumeroManager
{
    private $entityManager;
    private $kernel;
    private $logger;
    private $pageRepository;
    private $slugger;
    private $imageRepository;
    private $numeroRepository;

    public function __construct(EntityManagerInterface $entityManager, PageRepository $pageRepository, KernelInterface $kernel, LoggerInterface $logger = null, ImageRepository $imageRepository, NumeroRepository $numeroRepository, SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
        $this->kernel = $kernel;
        $this->imageRepository = $imageRepository;
        $this->numeroRepository = $numeroRepository;
        $this->logger = $logger;
        $this->pageRepository = $pageRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * for numero creation.
     */
    public function pdfToText(Numero $numero, string $action = 'add'): void
    {
        if ('update' == $action) {
            /**Remove old page => isDelete to 1 ***/
            $pages_old = $this->pageRepository->findBy(['numero' => $numero, 'isDeleted' => false]);
            foreach ($pages_old as $page_old) {
                $page_old->setIsDeleted(true);
                try {
                    $this->entityManager->persist($page_old);
                    $this->entityManager->flush();
                } catch (DBALException $exception) {
                    $this->logger->error($exception->getMessage());
                    throw new \Exception($exception->getMessage());
                } catch (\Throwable $exception) {
                    $this->logger->error($exception->getMessage());
                    throw new \Exception($exception->getMessage());
                }
            }

            /**Remove old image => isDelete to 1 ***/
            $images_old = $this->imageRepository->findBy(['numero' => $numero, 'isDeleted' => false]);
            foreach ($images_old as $image_old) {
                $image_old->setIsDeleted(true);
                try {
                    $this->entityManager->persist($image_old);
                    $this->entityManager->flush();
                } catch (DBALException $exception) {
                    $this->logger->error($exception->getMessage());
                    throw new \Exception($exception->getMessage());
                } catch (\Throwable $exception) {
                    $this->logger->error($exception->getMessage());
                    throw new \Exception($exception->getMessage());
                }
            }
        }

        /** Generate textuel parsing**/
        $parser = new Parser();
        $url = $this->kernel->getProjectDir().'/data/revues/'.$numero->getFileUri();
        $pdf = $parser->parseFile($url);
        $pages = $pdf->getPages();
        $num_page = 1;
        foreach ($pages as $page) {
            $p = new Page();
            $escape_content = Utils::clean_text($page->getText(), ['TOUT']);
            $escape_content = preg_replace('/[\x00-\x1F\x7F]/u', '', $escape_content);
            $escape_content = str_replace(["\r\n", "\n"], ' ', $escape_content);
            $escape_content = str_replace('\\', '', $escape_content);
            $p->setContent($escape_content);
            $p->setNumeroPage($num_page);
            $p->setNumero($numero);
            try {
                $this->entityManager->persist($p);
                $this->entityManager->flush();
            } catch (DBALException $exception) {
                $this->logger->error($exception->getMessage());
                throw new \Exception($exception->getMessage());
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
                throw new \Exception($exception->getMessage());
            }
            ++$num_page;
        }
    }

    /**
     * for numero creation.
     */
    public function pdfToImage(Numero $numero, string $action = 'add'): void
    {
        if ('update' == $action) {
            $images_old = $this->imageRepository->findBy(['numero' => $numero]);
            foreach ($images_old as $image_old) {
                $image_old->setIsDeleted(true);
                try {
                    $this->entityManager->persist($image_old);
                    $this->entityManager->flush();
                } catch (DBALException $exception) {
                    $this->logger->error($exception->getMessage());
                } catch (\Throwable $exception) {
                    $this->logger->error($exception->getMessage());
                }
            }
        }

        $filePath = $this->kernel->getProjectDir().'/data/revues/'.$numero->getFileUri();

        $fs = new Filesystem();

        if ($fs->exists($filePath)) {
            $pdf = new Pdf($filePath);
            $pages = $pdf->getNumberOfPages();

            for ($i = 1; $i <= $pages; ++$i) {
                $tmpFile = tmpfile();
                $tmpPath = stream_get_meta_data($tmpFile)['uri'];
                $pdf->setPage($i);
                $pdf->setOutputFormat('png');
                $pdf->setCompressionQuality(85);
                $pdf->saveImage($tmpPath);

                $safeFilename = $this->kernel->getProjectDir().'/data/revues/'.basename($numero->getFileUri(), '.pdf').'/'.$i.'.png';

                $image = new Image();
                $image->setFileUri(basename($numero->getFileUri(), '.pdf').'/'.$i.'.png');
                $image->setNumero($numero);
                list($width, $height) = getimagesize($safeFilename);
                $image->setWidth($width);
                $image->setHeight($height);
                $this->entityManager->persist($image);
                $this->entityManager->flush();
            }
        } else {
            //throw new FileNotFoundException($filePath);
        }
    }

    public function searchDci(Numero $numero): void
    {
        foreach ($numero->getRevue()->getClients() as $client) {
            foreach ($client->getDcis() as $dci) {
                if ($numero->isImage()) {
                    foreach ($numero->getImages() as $image) {
                        foreach ($image->getPages() as $page) {
                            foreach ($page->getBlocks() as $block) {
                                if (false !== mb_stripos($block->getText(), $dci->getTitle()) ||
                                    false !== mb_stripos($block->getText(), mb_strtolower($dci->getTitle())) ||
                                    false !== mb_stripos($block->getText(), mb_strtoupper($dci->getTitle())) ||
                                    false !== mb_stripos($block->getText(), Utils::no_accent($dci->getTitle()))) {
                                    $dci->addBlock($block);
                                    $this->entityManager->persist($dci);
                                }
                            }
                        }
                    }
                    $this->entityManager->flush();
                } else {
                    foreach ($numero->getPages() as $page) {
                        if (false !== mb_stripos($page->getContent(), $dci->getTitle()) ||
                            false !== mb_stripos($page->getContent(), mb_strtolower($dci->getTitle())) ||
                            false !== mb_stripos($page->getContent(), mb_strtoupper($dci->getTitle())) ||
                            false !== mb_stripos($page->getContent(), Utils::no_accent($dci->getTitle()))) {
                            $dci->addPage($page);
                            $this->entityManager->persist($dci);
                        }
                    }
                    // pdfToImage
                    foreach ($numero->getImages() as $image) {
                        foreach ($image->getPages() as $page) {
                            foreach ($page->getBlocks() as $block) {
                                if (false !== mb_stripos($block->getText(), $dci->getTitle()) ||
                                    false !== mb_stripos($block->getText(), mb_strtolower($dci->getTitle())) ||
                                    false !== mb_stripos($block->getText(), mb_strtoupper($dci->getTitle())) ||
                                    false !== mb_stripos($block->getText(), Utils::no_accent($dci->getTitle()))) {
                                    $dci->addBlock($block);
                                    $this->entityManager->persist($dci);
                                }
                            }
                        }
                    }
                    $this->entityManager->flush();
                }
            }
        }
    }
}
