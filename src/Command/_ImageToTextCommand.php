<?php

namespace App\Command;

use App\Manager\RevueManagement\ImageManager;
use App\Repository\RevueManagement\NumeroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @codeCoverageIgnore
 */
class _ImageToTextCommand extends Command
{
    protected static $defaultName = 'app:_image-to-text';
    protected static $defaultDescription = 'transfert the image to text';

    private $entityManager;
    private $numeroRepository;
    private $imageManager;

    public function __construct(EntityManagerInterface $entityManager, NumeroRepository $numeroRepository, ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
        $this->numeroRepository = $numeroRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
        ;
    }

    /**
     * for dev.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $arg1 = $input->getArgument('arg1');
        $io->note(sprintf('arg1: %s', $arg1));
        //$numeros = $this->numeroRepository->findBy(['isDeleted'=>false, 'isImage'=>false], ['updateDate'=>'DESC']);
        if ($arg1) {
            $numero = $this->numeroRepository->findOneBy(['id' => $arg1]);
            //foreach ($numeros as $numero) {
            if ($numero) {
                $io->note(sprintf('getImages: %s', \count($numero->getImages())));
                foreach ($numero->getImages() as $image) {
                    $io->note(sprintf('image: %s', $image->getFileUri()));
                    try {
                        $this->imageManager->imageToText($image);
                    } catch (\Throwable $exception) {
                        $io->note($exception->getMessage());
                    }
                }
            }
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
