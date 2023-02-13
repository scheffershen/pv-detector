<?php

namespace App\Command;

use App\Entity\SearchManagement\Dci;
use App\Entity\SearchManagement\Indexation;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\RevueManagement\PageRepository;
use App\Repository\SearchManagement\DciRepository;
use App\Repository\SearchManagement\IndexationRepository;
use App\Utils\WordSearch;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class DciIndexationCommand extends Command
{
    protected static $defaultName = 'app:dci-indexation';
    protected static $defaultDescription = 'Dcis indexing';

    private $dciRepository;
    private $indexationRepository;
    private $numeroRepository;
    private $pageRepository;
    private $manager;
    private $kernel;

    public function __construct(IndexationRepository $indexationRepository, DciRepository $dciRepository, NumeroRepository $numeroRepository, PageRepository $pageRepository, EntityManagerInterface $manager, KernelInterface $kernel)
    {
        $this->dciRepository = $dciRepository;
        $this->indexationRepository = $indexationRepository;
        $this->numeroRepository = $numeroRepository;
        $this->pageRepository = $pageRepository;
        $this->manager = $manager;
        $this->kernel = $kernel;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
            ->setHelp(
                'This command allows to indexing all dcis from database.'
            )
            //->addArgument('number', InputArgument::OPTIONAL, 'File CSV to import')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ds = DIRECTORY_SEPARATOR;                
        $fileLock = $this->kernel->getProjectDir().$ds.'var'.$ds.'log'.$ds.'dci-indexation.lock';

        $fs = new Filesystem();

        if (!$fs->exists($fileLock)) {
            file_put_contents($fileLock, '', FILE_APPEND | LOCK_EX);
        }
        
        $fp = fopen($fileLock, 'w') or die ('Cannot create lock file');
        
        if (flock($fp, LOCK_EX | LOCK_NB)) {        
            $this->manager->getConnection()->getConfiguration()->setSQLLogger(null);

            $io = new SymfonyStyle($input, $output);

            $start = new \DateTime();
            $io->info(['Start : '.$start->format('d-m-Y G:i:s')]);

            $dcis = $this->dciRepository->findBy(['isValid' => true, 'isIndexed' => false], ['updateDate' => 'ASC']);

            $numeros = $this->numeroRepository->findBy(['isValid' => true, 'state' => 'treatment'], ['updateDate' => 'ASC']);

            $total = 0;
            $indexed = 0;
            $failed = 0;
            $dcisIndexed = [];
            $io->progressStart(\count($dcis));

            foreach ($dcis as $dci) {
                ++$total;

                $this->removeOldIndexs($dci);
                foreach ($numeros as $numero) {
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
                                            if (!\in_array($dci->getTitle(), $dcisIndexed, true)) {
                                                array_push($dcisIndexed, $dci->getTitle());
                                            }
                                            //$dci_indexed = true;
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

                //if ($dci_indexed) {
                $dci->setIsIndexed(true);
                $this->manager->persist($dci);
                $this->manager->flush();
                //}

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
                // $this->mailer->sendMail([$this->params->get('admin_email')], $this->params->get('admin_email'), $this->translator->trans('dci.flash.indexed'), $this->twig->render('SearchManagement/emails/dci_indexation_notification.html.twig', [
                //         'dcisIndexed' => $dcisIndexed,
                //         'total' => $total,
                //         'indexed' => $indexed,
                //         'failed' => $failed,
                //     ])
                // );
            }

            $end = new \DateTime();
            $io->info(['End : '.$end->format('d-m-Y G:i:s')]);

        }

        if ($fs->exists($fileLock)) {
            unlink($fileLock);
        }

        return Command::SUCCESS;
    }

    private function removeOldIndexs(Dci $dci): void
    {
        $indexs = $this->indexationRepository->findBy(['dci' => $dci]);
        foreach ($indexs as $index) {
            $this->manager->remove($index);
        }
        $this->manager->flush();
    }
  
}