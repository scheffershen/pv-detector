<?php

namespace App\MessageHandler\RevueManagement;

use App\Mailer\MailerInterface;
use App\Manager\RevueManagement\ImageManager;
use App\Manager\RevueManagement\NumeroManager;
use App\Message\RevueManagement\NumeroMessage;
use App\Message\RevueManagement\PdfToImageMessage;
use App\Notification\RevueManagement\NumeroNotification;
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
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @codeCoverageIgnore
 */
class NumeroMessageHandler implements MessageHandlerInterface
{
    private $entityManager;
    private $kernel;
    private $slugger;
    private $logger;
    private $params;
    private $bus;
    private $notifier;
    private $workflow;
    private $pageRepository;
    private $numeroManager;
    private $imageManager;
    private $mailer;
    private $twig;
    private $translator;
    private $messageBus;

    public function __construct(EntityManagerInterface $entityManager, PageRepository $pageRepository, NumeroManager $numeroManager, ImageManager $imageManager, MessageBusInterface $bus, WorkflowInterface $numeroStateMachine, NotifierInterface $notifier, KernelInterface $kernel, SluggerInterface $slugger, LoggerInterface $logger, ParameterBagInterface $params, MailerInterface $mailer, Environment $twig, TranslatorInterface $translator)
    {
        $this->params = $params;
        $this->slugger = $slugger;
        $this->kernel = $kernel;
        $this->messageBus = $bus;
        $this->notifier = $notifier;
        $this->workflow = $numeroStateMachine;
        $this->logger = $logger;
        $this->numeroManager = $numeroManager;
        $this->imageManager = $imageManager;
        $this->pageRepository = $pageRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;
    }

    public function __invoke(NumeroMessage $numeroMessage)
    {
        $numero = $numeroMessage->getNumero();

        if ($this->workflow->can($numero, 'submit')) {
            if ($numero->isImage()) {
                foreach ($numero->getImages() as $image) {
                    try {
                        $this->imageManager->imageToText($image);
                    } catch (\Throwable $exception) {
                        //throw new \Exception($exception->getMessage());
                        $this->logger->error($exception->getMessage());
                    }
                }
                $this->workflow->apply($numero, 'submit');
                $this->entityManager->flush();
            } else {
                try {
                    $this->numeroManager->pdfToText($numero);
                    //$this->logger->error("dispatch(new PdfToImageMessage");
                    //$this->messageBus->dispatch(new PdfToImageMessage($numero));
                } catch (\Throwable $exception) {
                    //throw new \Exception($exception->getMessage());
                    $this->logger->error($exception->getMessage());
                }
            }
            // bug
            //$notification = new NumeroNotification($numero, "add");
            //$this->notifier->send($notification, ...$this->notifier->getAdminRecipients());
            $this->mailer->sendMail([$this->params->get('admin_email')], $this->params->get('admin_email'), $this->translator->trans('numero.flash.created'), $this->twig->render('RevueManagement/emails/numero_notification.html.twig', ['numero' => $numero, 'action' => 'add'])
                );
        //$this->messageBus->dispatch(new NumeroMessage($numero));
        } elseif ($this->workflow->can($numero, 'treatment')) {
            //try {
                //$this->numeroManager->searchDci($numero);
                //$this->workflow->apply($numero, 'treatment');
                //$this->entityManager->flush();
            //} catch (\Throwable $exception) {
                //$this->workflow->apply($comment, 'reject');
                //$this->logger->error($exception->getMessage());
            //}
        } elseif ($this->logger) {
            $this->logger->debug('Dropping numero message', ['numero' => $numero->getId(), 'state' => $numero->getState()]);
        }
    }
}
