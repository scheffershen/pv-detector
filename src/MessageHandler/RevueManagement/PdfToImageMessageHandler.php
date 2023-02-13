<?php

namespace App\MessageHandler\RevueManagement;

use App\Manager\RevueManagement\ImageManager;
use App\Manager\RevueManagement\NumeroManager;
use App\Message\RevueManagement\PdfToImageMessage;
use App\Repository\RevueManagement\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class PdfToImageMessageHandler implements MessageHandlerInterface
{
    private $entityManager;
    private $kernel;
    private $slugger;
    private $logger;
    private $params;
    private $bus;
    private $workflow;
    private $pageRepository;
    private $numeroManager;
    private $imageManager;
    private $messageBus;

    public function __construct(EntityManagerInterface $entityManager, PageRepository $pageRepository, NumeroManager $numeroManager, ImageManager $imageManager, MessageBusInterface $bus, WorkflowInterface $numeroStateMachine, NotifierInterface $notifier, KernelInterface $kernel, SluggerInterface $slugger, LoggerInterface $logger, ParameterBagInterface $params)
    {
        $this->params = $params;
        $this->slugger = $slugger;
        $this->kernel = $kernel;
        $this->messageBus = $bus;
        $this->workflow = $numeroStateMachine;
        $this->logger = $logger;
        $this->numeroManager = $numeroManager;
        $this->imageManager = $imageManager;
        $this->pageRepository = $pageRepository;
        $this->entityManager = $entityManager;
    }

    public function __invoke(PdfToImageMessage $pdfToImageMessage)
    {
        $this->logger->error('__invoke(PdfToImageMessage');
        $numero = $pdfToImageMessage->getNumero();

        $this->numeroManager->pdfToImage($numero);
        foreach ($numero->getImages() as $image) {
            try {
                $this->imageManager->imageToText($image);
            } catch (\Throwable $exception) {
                throw new \Exception($exception->getMessage());
            }
        }
    }
}
