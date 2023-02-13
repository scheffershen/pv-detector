<?php

namespace App\MessageHandler\RevueManagement;

use App\Entity\RevueManagement\Numero;
use App\Manager\RevueManagement\ImageManager;
use App\Manager\RevueManagement\NumeroManager;
use App\Message\RevueManagement\NumeroUpdateMessage;
use App\Message\RevueManagement\PdfToImageUpdateMessage;
use App\Notification\RevueManagement\NumeroNotification;
use App\Repository\RevueManagement\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @codeCoverageIgnore
 */
class NumeroUpdateMessageHandler implements MessageHandlerInterface
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

    public function __invoke(NumeroUpdateMessage $numeroMessage)
    {
        $numero = $numeroMessage->getNumero();
        $old_numero = json_decode($numeroMessage->getOldNumero(), true);

        if ($this->workflow->can($numero, 'submit')) {
            if ($numero->isImage()) {
                foreach ($numero->getImages() as $image) {
                    if (!isset($old_numero['images'][$image->getId()])) {
                        try {
                            $this->imageManager->imageToText($image);
                        } catch (\Throwable $exception) {
                            throw new \Exception($exception->getMessage());
                        }
                    } elseif ($old_numero['images'][$image->getId()]['fileUri'] !== $image->getFileUri()) {
                        try {
                            $this->imageManager->imageToText($image, 'update');
                        } catch (\Throwable $exception) {
                            throw new \Exception($exception->getMessage());
                        }
                    }
                }
                $this->workflow->apply($numero, 'submit');
                $this->entityManager->flush();
            } else {
                try {
                    $this->numeroManager->pdfToText($numero, 'update');
                    //$this->logger->error("dispatch(new PdfToImageUpdateMessage");
                    //$this->messageBus->dispatch(new PdfToImageUpdateMessage($numero, "update"));
                } catch (\Throwable $exception) {
                    throw new \Exception($exception->getMessage());
                }
            }
            // bug
            // $notification = new NumeroNotification($numero, "update");
            // $this->notifier->send($notification, ...$this->notifier->getAdminRecipients());

            //$this->workflow->apply($numero, 'submit');
            //$this->entityManager->flush();
            //$this->messageBus->dispatch(new NumeroUpdateMessage(json_encode($old_numero), $numero));
        } elseif ($this->workflow->can($numero, 'treatment')) {
            //try {
                //$this->numeroManager->searchDci($numero);
                //$this->workflow->apply($numero, 'treatment');
                //$this->entityManager->flush();
            //} catch (\Throwable $exception) {
                //$this->workflow->apply($comment, 'reject');
            //}
        } elseif ($this->logger) {
            $this->logger->debug('Dropping numero message', ['numero' => $numero->getId(), 'state' => $numero->getState()]);
        }
    }
}
