<?php

namespace App\Command;

use App\Entity\RevueManagement\Image;
use App\Mailer\MailerInterface;
use App\Manager\RevueManagement\ImageManager;
use App\Notification\RevueManagement\NumeroStateNotification;
use App\Repository\RevueManagement\ImageRepository;
use App\Repository\RevueManagement\NumeroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Spatie\PdfToImage\Pdf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @codeCoverageIgnore
 */
class _PdfToImagesCommand extends Command
{
    protected static $defaultName = 'app:_pdf-to-images';
    protected static $defaultDescription = 'transfert the pdf to images, and then transfert the image to textes';

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
        $this->logger = $supervisorLogger;
        $this->kernel = $kernel;
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

        $numeros = $this->numeroRepository->findBy(['isDeleted' => false, 'isImage' => false, 'state' => 'submitted'], ['updateDate' => 'ASC']);

        foreach ($numeros as $numero) {            
            // $_images = $this->imageRepository->findBy(['numero' => $numero, 'isDeleted' => false]);

            // if (\count($_images) > 0) { // if this pdf has old images
            //     foreach ($_images as $_image) { 
            //         $_image->setIsDeleted(true);
            //         $this->entityManager->persist($_image);
            //         $this->entityManager->flush();
            //     }
            // }

            $io->note(sprintf('Numero id: %d', $numero->getId() ));
            $io->note(sprintf('Numero: %s', $numero->getTitle()));
            $this->logger->info(sprintf('PdfToImagesCommand, Numero id: %d', $numero->getId() ));
            $this->logger->info(sprintf('PdfToImagesCommand, Numero: %s', $numero->getTitle()));

            $ds = DIRECTORY_SEPARATOR;                
            $filePath = $this->kernel->getProjectDir().$ds.'data'.$ds.'revues'.$ds.$numero->getFileUri();

            $fs = new Filesystem();

            if ($fs->exists($filePath)) {
                $pdf = new Pdf($filePath);
                $pages = $pdf->getNumberOfPages();
                $num_page = 1;
                for ($i = 1; $i <= $pages; ++$i) {
                    $io->success('Creating a temp image file for page #'.$i);
                    $this->logger->info(sprintf('PdfToImagesCommand, Creating a temp image file for page: #%d', $i));

                    $tmpFile = tmpfile();
                    $tmpPath = stream_get_meta_data($tmpFile)['uri'];
                    $io->success('temp image file: '.$tmpPath.' for page #'.$i);
                    $this->logger->info(sprintf('PdfToImagesCommand, temp image file: %s for page #%d', $tmpPath, $i));

                    $pdf->setPage($i);
                    $pdf->setOutputFormat('png');
                    $pdf->setCompressionQuality(85);
                    $pdf->saveImage($tmpPath);

                    $safeFilename = $this->kernel->getProjectDir().$ds.'data'.$ds.'revues'.$ds.basename($numero->getFileUri(), '.pdf').'/'.$i.'.png';

                    $io->success('image file: '.$safeFilename.' for page #'.$i);
                    $this->logger->info(sprintf('PdfToImagesCommand, image file: %s for page #%d', $safeFilename, $i));

                    $fs->copy($tmpPath, $safeFilename, true);

                    $image = new Image();
                    $image->setFileUri(basename($numero->getFileUri(), '.pdf').'/'.$i.'.png');
                    $image->setNumero($numero);
                    $image->setNumeroPage($num_page);
                    list($width, $height) = getimagesize($safeFilename);
                    $image->setWidth($width);
                    $image->setHeight($height);
                    $this->entityManager->persist($image);
                    $this->entityManager->flush();

                    ++$num_page;

                    try {
                        $this->imageManager->imageToText($image);
                    } catch (\Throwable $exception) {
                        $io->note($exception->getMessage());
                        $this->logger->info(sprintf('PdfToImagesCommand, exception: %s', $exception->getMessage()));
                    }
                }
            } else {
                //throw new FileNotFoundException($filePath);
                $io->success(sprintf('FileNotFoundException: %s', $filePath));
                $this->logger->info(sprintf('PdfToImagesCommand, FileNotFoundException: %s', $filePath));
            }
            // bug
            //$notification = new NumeroStateNotification($numero);
            //$this->notifier->send($notification, ...$this->notifier->getAdminRecipients());
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
