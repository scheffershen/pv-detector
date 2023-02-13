<?php

namespace App\Command;

use App\Mailer\MailerInterface;
use App\Manager\RevueManagement\ImageManager;
use App\Repository\RevueManagement\ImageRepository;
use App\Repository\RevueManagement\NumeroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @codeCoverageIgnore
 */
class _PdfImagesToTextesCommand extends Command
{
    protected static $defaultName = 'app:_pdfImages-to-textes';
    protected static $defaultDescription = 'transfert the pdf Images to textes';

    private $entityManager;
    private $kernel;
    private $slugger;
    private $logger;
    private $notifier;
    private $workflow;
    private $imageManager;
    private $imageRepository;
    private $numeroRepository;
    private $params;
    private $mailer;
    private $twig;
    private $translator;

    public function __construct(EntityManagerInterface $entityManager, ImageRepository $imageRepository, NumeroRepository $numeroRepository, ImageManager $imageManager, WorkflowInterface $numeroStateMachine, ParameterBagInterface $params, NotifierInterface $notifier, KernelInterface $kernel, SluggerInterface $slugger, MailerInterface $mailer, Environment $twig, TranslatorInterface $translator, LoggerInterface $supervisorLogger)
    {
        $this->params = $params;
        $this->slugger = $slugger;
        $this->kernel = $kernel;
        $this->logger = $supervisorLogger;
        $this->notifier = $notifier;
        $this->workflow = $numeroStateMachine;
        $this->imageManager = $imageManager;
        $this->imageRepository = $imageRepository;
        $this->numeroRepository = $numeroRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $numeros = $this->numeroRepository->findBy(['isDeleted' => false, 'isImage' => true, 'state' => 'submitted'], ['updateDate' => 'ASC']);

        foreach ($numeros as $numero) {
            $io->note(sprintf('Numero id: %d', $numero->getId() ));            
            $io->note(sprintf('Numbre des images: %d', \count($numero->getImages()) ));
            $this->logger->info(sprintf('PdfImagesToTextesCommand, Numero id: %d', $numero->getId() ));     
            $this->logger->info(sprintf('PdfImagesToTextesCommand, Numbre des images: %d', \count($numero->getImages()) ));

            foreach ($numero->getImages() as $image) {
                $io->note(sprintf('image: %s', $image->getFileUri()));
                $this->logger->info(sprintf('PdfImagesToTextesCommand, image: %s', $image->getFileUri()));

                try {
                    $this->imageManager->imageToText($image);

                } catch (\Throwable $exception) {
                    $io->note($exception->getMessage());
                    $this->logger->info(sprintf('PdfImagesToTextesCommand, exception: %s', $exception->getMessage()));
                }
            }

            $this->mailer->sendMail([$this->params->get('admin_email')], $this->params->get('admin_email'), $this->translator->trans('numero.flash.updated'), $this->twig->render('RevueManagement/emails/numero_state_notification.html.twig', ['numero' => $numero])
            );
            
            if ($this->workflow->can($numero, 'submit')) {
                $this->workflow->apply($numero, 'submit');
                $this->entityManager->flush();
            }
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
