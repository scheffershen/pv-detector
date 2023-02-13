<?php

namespace App\Command;

use App\Entity\LovManagement\Categorie;
use App\Entity\SearchManagement\Dci;
use App\Entity\UserManagement\Client;
use App\Repository\LovManagement\CategorieRepository;
use App\Repository\LovManagement\JourRepository;
use App\Repository\SearchManagement\DciRepository;
use App\Repository\UserManagement\ClientRepository;
use App\Repository\UserManagement\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class ImportDciLaboCommand extends Command
{
    protected static $defaultName = 'app:dci-labo:import';
    protected static $defaultDescription = 'Import dcis from CSV file';

    private static $supportedHeader = [
        'produit',
        'labo',
    ];

    /**
     * @var SymfonyStyle
     * php bin/console app:dci-labo:import import/ListeProduitsLabos.csv
     */
    private $io;
    private $manager;
    private $dciRepository;
    private $categorieRepository;
    private $jourRepository;
    private $clientRepository;
    private $userRepository;
    private $translator;
    private $slugger;

    public function __construct(DciRepository $dciRepository, CategorieRepository $categorieRepository, JourRepository $jourRepository, ClientRepository $clientRepository, UserRepository $userRepository, TranslatorInterface $translator, EntityManagerInterface $manager, SluggerInterface $slugger)
    {
        $this->clientRepository = $clientRepository;
        $this->dciRepository = $dciRepository;
        $this->categorieRepository = $categorieRepository;
        $this->jourRepository = $jourRepository;
        $this->userRepository = $userRepository;
        $this->translator = $translator;
        $this->slugger = $slugger;
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
            ->setHelp(
                'This command allows to import dci from a CSV file, which are formatted like CSV exports.'.PHP_EOL.
                'Imported dcis and clients and will be matched by name.'.PHP_EOL.
                'Supported columns names: '.implode('; ', self::$supportedHeader).PHP_EOL
            )
            ->addArgument('file', InputArgument::REQUIRED, 'File CSV to import')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Veille PV importer: csv "produit;categorie;labo" import');

        $csvFile = $input->getArgument('file');
        if (!file_exists($csvFile)) {
            $this->io->error('File not existing: '.$csvFile);

            return 1;
        }

        if (!is_readable($csvFile)) {
            $this->io->error('File cannot be read: '.$csvFile);

            return 2;
        }

        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(';');

        $header = $csv->getHeader();
        if (!$this->validateHeader($header)) {
            $this->io->error(
                sprintf(
                    'Found invalid CSV. The header: '.PHP_EOL.
                    '%s'.PHP_EOL.
                    'did not match the expected structure: '.PHP_EOL.
                    '%s',
                    implode('; ', $header),
                    implode('; ', self::$supportedHeader)
                )
            );

            return 5;
        }

        $total = 0;
        $imported = 0;
        $failed = 0;

        $this->io->progressStart($csv->count());

        foreach ($csv as $row) {
            ++$total;
            try {

                if (!empty($row['categorie']) && !$catgorie = $this->categorieRepository->findOneByTitle($cat = trim($row['categorie'])) ) {
                    $sort = ($this->categorieRepository->findByLastCategorie())->getSort() + 1;
                    $catgorie = new Categorie();
                    $catgorie->setTitle($cat);
                    $catgorie->setCode($this->slugger->slug($cat));
                    $catgorie->setSort($sort);
                    $catgorie->setCreateUser($this->getUser());
                    $catgorie->setUpdateUser($this->getUser());
                    $this->manager->persist($catgorie);
                    $this->manager->flush();
                }

                if (!empty($row['produit']) && !$dci = $this->dciRepository->findOneByTitle($produit = trim($row['produit'])) ) {
                    $dci = new Dci();
                    $dci->setTitle($produit);
                    $dci->setCategorie($catgorie);
                    $dci->setCreateUser($this->getUser());
                    $dci->setUpdateUser($this->getUser());
                    $this->manager->persist($dci);
                    $this->manager->flush();
                }

                //if (!$client = $this->clientRepository->findOneByName($labo = $row['labo'])) {
                if (!empty($row['labo'])) {
                    $labos = explode("|", $row['labo']);

                    foreach ($labos as $labo) {
                        if (!$client = $this->clientRepository->findOneByCode(trim($labo))) {
                            $client = new Client();
                            $client->setName(trim($labo));
                            $client->setCode(trim($labo));
                            $client->setAdress('no data');
                            $client->addDci($dci);
                            $client->setJourBilanHebdomadaire($this->jourRepository->findOneBy(['id' => 1]));
                            $client->setRespondableClient($this->getUser());
                            $client->setCreateUser($this->getUser());
                            $client->setUpdateUser($this->getUser());
                            $this->manager->persist($client);
                        } else {
                            $client->addDci($dci);
                            $this->manager->persist($client);
                        }
                        $this->manager->flush();
                    }
                }
                ++$imported;
            } catch (\Exception $ex) {
                $this->io->error(sprintf('Failed importing csv row %s with: %s', $total, $ex->getMessage()));
                ++$failed;
            }

            $this->io->progressAdvance();
        }

        $this->io->progressFinish();

        if ($failed > 0) {
            $this->io->warning(sprintf('Failed validating %s rows', $failed));
        }

        if ($imported > 0) {
            $this->io->success(sprintf('Imported %s rows', $imported));
        }

        return Command::SUCCESS;
    }

    private function validateHeader(array $header)
    {
        $result = array_diff(self::$supportedHeader, $header);

        return empty($result);
    }

    private function getUser()
    {
        return $this->userRepository->findOneBy(['id' => 1]);
    }
}
