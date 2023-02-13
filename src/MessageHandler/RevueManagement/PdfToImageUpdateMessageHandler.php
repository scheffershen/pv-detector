<?php

namespace App\MessageHandler\RevueManagement;

use App\Manager\RevueManagement\ImageManager;
use App\Manager\RevueManagement\NumeroManager;
use App\Message\RevueManagement\PdfToImageUpdateMessage;
use App\Repository\RevueManagement\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class PdfToImageUpdateMessageHandler implements MessageHandlerInterface
{
    private $entityManager;
    private $kernel;
    private $slugger;
    private $logger;
    private $bus;
    private $workflow;
    private $notifier;
    private $pageRepository;
    private $numeroManager;
    private $imageManager;
    private $messageBus;

    public function __construct(EntityManagerInterface $entityManager, PageRepository $pageRepository, NumeroManager $numeroManager, ImageManager $imageManager, MessageBusInterface $bus, WorkflowInterface $numeroStateMachine, NotifierInterface $notifier, KernelInterface $kernel, SluggerInterface $slugger, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->slugger = $slugger;
        $this->kernel = $kernel;
        $this->messageBus = $bus;
        $this->workflow = $numeroStateMachine;
        $this->notifier = $notifier;
        $this->numeroManager = $numeroManager;
        $this->imageManager = $imageManager;
        $this->pageRepository = $pageRepository;
        $this->entityManager = $entityManager;
    }

    public function __invoke(PdfToImageUpdateMessage $pdfToImageMessage)
    {
        $this->logger->error('__invoke(PdfToImageMessage');
        $numero = $pdfToImageMessage->getNumero();
        $this->numeroManager->pdfToImage($numero, 'update');
        foreach ($numero->getImages() as $image) {
            try {
                $this->imageManager->imageToText($image);
            } catch (\Throwable $exception) {
                throw new \Exception($exception->getMessage());
            }
        }
    }
}
