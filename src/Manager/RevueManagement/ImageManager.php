<?php

namespace App\Manager\RevueManagement;

use App\Entity\RevueManagement\Block;
use App\Entity\RevueManagement\Image;
use App\Entity\RevueManagement\Page;
use App\Entity\RevueManagement\Vertice;
use App\Repository\RevueManagement\PageRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Google\Cloud\Vision\V1\ImageAnnotatorClient as GoogleImageAnnotatorClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;

class ImageManager
{
    private $entityManager;
    private $kernel;
    private $logger;
    private $keyFilePath;
    private $pageRepository;

    public function __construct(string $keyFilePath, EntityManagerInterface $entityManager, PageRepository $pageRepository, KernelInterface $kernel, LoggerInterface $logger = null)
    {
        $this->kernel = $kernel;
        $this->keyFilePath = $keyFilePath;
        $this->pageRepository = $pageRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function imageToText(Image $image, string $action = 'add'): void
    {
        if ('update' == $action) {
            $pages_old = $this->pageRepository->findBy(['image' => $image]);
            foreach ($pages_old as $page_old) {
                $page_old->setIsDeleted(true);
                try {
                    $this->entityManager->persist($page_old);
                    $this->entityManager->flush();
                } catch (DBALException $exception) {
                    $this->logger->error($exception->getMessage());
                } catch (\Throwable $exception) {
                    $this->logger->error($exception->getMessage());
                }
            }
        }

        $filePath = $this->kernel->getProjectDir().'/data/revues/'.$image->getFileUri();
        $fs = new Filesystem();
        if ($fs->exists($filePath)) {
            $imageAnnotator = new GoogleImageAnnotatorClient(['credentials' => $this->keyFilePath]);
            $image_content = file_get_contents($filePath);
            $response = $imageAnnotator->documentTextDetection($image_content);
            $annotation = $response->getFullTextAnnotation();

            // print out detailed and structured information about document text
            if ($annotation) {
                $num_page = 1;
                foreach ($annotation->getPages() as $page) {
                    $_page = new Page();
                    $_page->setContent('');
                    $blocksText = '';
                    $_page->setNumeroPage($num_page);
                    $_page->setImage($image);
                    foreach ($page->getBlocks() as $block) {
                        $_block = new Block();
                        $_block->setPage($_page);
                        $block_text = '';
                        foreach ($block->getParagraphs() as $paragraph) {
                            foreach ($paragraph->getWords() as $word) {
                                foreach ($word->getSymbols() as $symbol) {
                                    $block_text .= $symbol->getText();
                                }
                                $block_text .= ' ';
                            }
                            $block_text .= "\n";
                        }
                        $_block->setText($block_text);
                        $blocksText .= $block_text;
                        $_block->setConfidence($block->getConfidence());
                        $this->entityManager->persist($_block);
                        $vertices = $block->getBoundingBox()->getVertices();
                        foreach ($vertices as $vertex) {
                            $_vertice = new Vertice();
                            $_vertice->setBlock($_block);
                            $_vertice->setX($vertex->getX());
                            $_vertice->setY($vertex->getY());
                            $this->entityManager->persist($_vertice);
                        }
                    }
                    $_page->setBlocksText($blocksText);
                    $this->entityManager->persist($_page);
                    try {
                        $this->entityManager->flush();
                    } catch (DBALException $exception) {
                        $this->logger->error($exception->getMessage());
                    } catch (\Throwable $exception) {
                        $this->logger->error($exception->getMessage());
                    }
                    ++$num_page;
                }
            }

            $imageAnnotator->close();
        } else {
            throw new FileNotFoundException($filePath);
        }
    }
}
