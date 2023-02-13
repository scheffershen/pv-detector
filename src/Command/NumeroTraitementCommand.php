<?php

namespace App\Command;

use App\Entity\RevueManagement\Image;
use App\Mailer\MailerInterface;
use App\Manager\RevueManagement\ImageManager;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\UserManagement\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Spatie\PdfToImage\Pdf;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class NumeroTraitementCommand extends Command
{
    protected static $defaultName = 'app:numero-traitement';
    protected static $defaultDescription = 'transfert the pdf to images to texte';

    private $entityManager;
    private $kernel;
    private $logger;
    private $workflow;
    private $imageManager;
    private $numeroRepository;
    private $userRepository;
    private $params;
    private $mailer;
    private $twig;
    private $translator;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, NumeroRepository $numeroRepository, ImageManager $imageManager, WorkflowInterface $numeroStateMachine, ParameterBagInterface $params, MailerInterface $mailer, Environment $twig, TranslatorInterface $translator, KernelInterface $kernel, LoggerInterface $supervisorLogger)
    {
        $this->kernel = $kernel;
        $this->logger = $supervisorLogger;
        $this->workflow = $numeroStateMachine;
        $this->imageManager = $imageManager;
        $this->userRepository = $userRepository;
        $this->numeroRepository = $numeroRepository;
        $this->entityManager = $entityManager;
        $this->params = $params;
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
        $ds = DIRECTORY_SEPARATOR;                
        $fileLock = $this->kernel->getProjectDir().$ds.'var'.$ds.'log'.$ds.'numero-traitement.lock';

        $fs = new Filesystem();
        if (!$fs->exists($fileLock)) {
            file_put_contents($fileLock, '', FILE_APPEND | LOCK_EX);
        }
        
        // create a file for locking write         
        $fp = fopen($fileLock, 'w') or die ('Cannot create lock file');
        
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $io = new SymfonyStyle($input, $output);

            $numeros = $this->numeroRepository->findBy(['isValid' => true, 'state' => 'submitted'], ['updateDate' => 'ASC']);
            $users= $this->userRepository->findAllByRoleGestionnaireLecteur();
            
            $cc = []; 
            foreach ($users as $user) {
                $cc[] = $user->getEmail(); 
            }

            foreach ($numeros as $numero) {
                $numero_processed = false;
                $io->note(sprintf('Numero id: %d', $numero->getId() ));            
                $io->note(sprintf('Numbre des images: %d', \count($numero->getImages()) ));
                //$this->logger->info(sprintf('PdfImagesToTextesCommand, Numero id: %d', $numero->getId() ));     
                $this->logger->info(sprintf('PdfImagesToTextesCommand, Numbre des images: %d', \count($numero->getImages()) ));

                // numero is the images
                if ($numero->isImage()) {
                    foreach ($numero->getImages() as $image) {
                        $io->note(sprintf('image: %s', $image->getFileUri()));
                        $this->logger->info(sprintf('PdfImagesToTextesCommand, image: %s', $image->getFileUri()));

                        try {
                            $this->imageManager->imageToText($image);
                            $numero_processed = true;
                        } catch (\Throwable $exception) {
                            $io->note($exception->getMessage());
                            $this->logger->info(sprintf('PdfImagesToTextesCommand, exception: %s', $exception->getMessage()));
                            $numero_processed = false;                        
                        }
                    }
                } else {
                    // numero is the pdf
                    $filePath = $this->kernel->getProjectDir().$ds.'data'.$ds.'revues'.$ds.$numero->getFileUri();

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

                            $safeFilename = $this->kernel->getProjectDir().$ds.'data'.$ds.'revues'.$ds.basename($numero->getFileUri(),'.pdf').$ds.$i.'.png';
                            
                            if (!$fs->exists($safeFilename)) {

                                $io->success('image file: '.$safeFilename.' for page #'.$i);
                                $this->logger->info(sprintf('PdfToImagesCommand, image file: %s for page #%d', $safeFilename, $i));
                            
                                $fs->copy($tmpPath, $safeFilename, true);
                                $image = new Image();
                                $image->setFileUri(basename($numero->getFileUri(), '.pdf').$ds.$i.'.png');
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
                                    $numero_processed = true;                    
                                } catch (\Throwable $exception) {
                                    $io->note($exception->getMessage());
                                    $this->logger->info(sprintf('PdfToImagesCommand, exception: %s', $exception->getMessage()));
                                    $numero_processed = false;               
                                }
                            }
 
                        }
                    } else {
                        //throw new FileNotFoundException($filePath);
                        $io->success(sprintf('FileNotFoundException: %s', $filePath));
                        $this->logger->info(sprintf('PdfToImagesCommand, FileNotFoundException: %s', $filePath));
                        $numero_processed = false;                       
                    }
                }

                if ($numero_processed) {
                    if ($this->workflow->can($numero, 'submit')) {
                        $this->workflow->apply($numero, 'submit');
                        $this->entityManager->flush();
                    } 
                } else {
                    if ($this->workflow->can($numero, 'reject')) {
                        $this->workflow->apply($numero, 'reject');
                        $this->entityManager->flush();
                    }  
                }

                $this->mailer->sendMail([
                    $this->params->get('admin_email')], 
                    $this->params->get('admin_email'), 
                    $this->translator->trans('numero.flash.updated'), 
                    $this->twig->render('RevueManagement/emails/numero_state_notification.html.twig', 
                        ['numero' => $numero ]),
                    \array_unique($cc)
                );

            }

            $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

            fflush($fp);            // libère le contenu avant d'enlever le verrou
            flock($fp, LOCK_UN);    // Enlève le verrou
        }

        if ($fs->exists($fileLock)) {
            unlink($fileLock);
        }

        return Command::SUCCESS;
    }
 
}
