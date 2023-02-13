<?php

namespace App\Command;

use App\Entity\RevueManagement\Numero;
use App\Entity\SearchManagement\Indexation;
use App\Mailer\MailerInterface;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\RevueManagement\PageRepository;
use App\Repository\SearchManagement\DciRepository;
use App\Repository\SearchManagement\IndexationRepository;
use App\Repository\UserManagement\UserRepository;
use App\Utils\WordSearch;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class NumeroReIndexationCommand extends Command
{
    protected static $defaultName = 'app:numero-reindexation';
    protected static $defaultDescription = 'Numeros re-indexing';

    private $dciRepository;
    private $indexationRepository;
    private $numeroRepository;
    private $pageRepository;
    private $userRepository;
    private $manager;
    private $params;
    private $mailer;
    private $twig;
    private $translator;
    private $kernel;

    public function __construct(UserRepository $userRepository, IndexationRepository $indexationRepository, DciRepository $dciRepository, NumeroRepository $numeroRepository, PageRepository $pageRepository, ParameterBagInterface $params, EntityManagerInterface $manager, MailerInterface $mailer, Environment $twig, TranslatorInterface $translator, KernelInterface $kernel)
    {
        $this->dciRepository = $dciRepository;
        $this->indexationRepository = $indexationRepository;
        $this->numeroRepository = $numeroRepository;
        $this->userRepository = $userRepository;
        $this->pageRepository = $pageRepository;
        $this->manager = $manager;
        $this->kernel = $kernel;
        $this->params = $params;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
            ->setHelp(
                'This command allows to re-indexing the numeros from database.'
            )
            //->addArgument('number', InputArgument::OPTIONAL, 'the number of limit')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ds = DIRECTORY_SEPARATOR;                
        $fileLock = $this->kernel->getProjectDir().$ds.'var'.$ds.'log'.$ds.'numero-re-indexation.lock';

        $fs = new Filesystem();
        
        if (!$fs->exists($fileLock)) {
            file_put_contents($fileLock, '', FILE_APPEND | LOCK_EX);
        }
        
        $fp = fopen($fileLock, 'w') or die ('Cannot create lock file');
        
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            // Turning off doctrine default logs queries for saving memory
            $this->manager->getConnection()->getConfiguration()->setSQLLogger(null);
            
            $io = new SymfonyStyle($input, $output);

            $start = new \DateTime();
            $io->info(['Start : '.$start->format('d-m-Y G:i:s')]);

            $indexs = $this->indexationRepository->findAll();
            foreach ($indexs as $index) {
                $this->manager->remove($index);
            }
            $this->manager->flush();

            $dcis = $this->dciRepository->findBy(['isValid' => true], ['updateDate' => 'ASC']);

            $numeros = $this->numeroRepository->findBy(['isValid' => true, 'isIndexed' => true], ['updateDate' => 'ASC']);            
            $users= $this->userRepository->findAllByRoleGestionnaireLecteur();
            
            $cc = []; 
            foreach ($users as $user) {
                $cc[] = $user->getEmail(); 
            }

            $total = 0;
            $indexed = 0;
            $failed = 0;
            $numerosIndexed = [];
            $io->progressStart(\count($numeros));

            foreach ($numeros as $numero) {
                ++$total;

                foreach ($dcis as $dci) {
                    foreach ($numero->getImages() as $image) {
                        if (!$image->isDeleted()) {
                            foreach ($image->getPages() as $page) {
                                if (!$page->isDeleted()) {  
                                    if ( WordSearch::find_word($page->getBlocksText(), $dci->getTitle())) {   
                                        $indexation = new Indexation();
                                        $indexation->setNumero($numero);
                                        $indexation->setImage($image);
                                        $indexation->setPage($page);
                                        $indexation->setDci($dci);
                                        
                                        $nb_occurrence = WordSearch::count_exact_words($page->getBlocksText(), $dci->getTitle());
                                        $nb_occurrence = ($nb_occurrence > 0)?$nb_occurrence:1;
                                        $indexation->setOccurrence($nb_occurrence);
                                        
                                        try {
                                            $this->manager->persist($indexation);
                                            $this->manager->flush();
                                            ++$indexed;
                                            if (!\in_array($numero->getTitle(), $numerosIndexed, true)) {
                                                array_push($numerosIndexed, $numero->getTitle());
                                            }
                                        } catch (\Exception $ex) {
                                            $io->error(sprintf('Failed indexing: %s', $ex->getMessage()));
                                            ++$failed;
                                        }
                                        
                                    }
                                }
                            }
                        }
                    }
                }

                $numero->setIsIndexed(true);
                $this->manager->persist($numero);
                $this->manager->flush();

                /*$this->mailer->sendMail([
                    $this->params->get('admin_email')], 
                    $this->params->get('admin_email'), 
                    $this->translator->trans('numero.flash.updated'), 
                    $this->twig->render('RevueManagement/emails/numero_state_notification.html.twig', 
                        ['numero' => $numero ]),
                    \array_unique($cc)
                );*/

                $io->progressAdvance();
            }

            $io->progressFinish();

            if ($failed > 0) {
                $io->warning(sprintf('Number of error: %s', $failed));
            }

            if ($indexed > 0) {
                $io->success(sprintf('Number of indexing: %s', $indexed));
            }

            if ($total > 0) {
                // $this->mailer->sendMail([$this->params->get('admin_email')], $this->params->get('admin_email'), $this->translator->trans('dci.flash.indexed'), $this->twig->render('SearchManagement/emails/numero_indexation_notification.html.twig', [
                //         'numerosIndexed' => $numerosIndexed,
                //         'total' => $total,
                //         'indexed' => $indexed,
                //         'failed' => $failed,
                //     ])
                // );

                // $command = $this->getApplication()->find('fos:elastica:populate');
                // $greetInput = new ArrayInput([]);
                // $returnCode = $command->run($greetInput, $output);                 
            }

            $end = new \DateTime();
            $io->info(['End : '.$end->format('d-m-Y G:i:s')]);
        }

        if ($fs->exists($fileLock)) {
            unlink($fileLock);
        }

        return Command::SUCCESS;
    }

}
