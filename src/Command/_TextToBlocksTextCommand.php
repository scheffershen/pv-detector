<?php

namespace App\Command;

use App\Mailer\MailerInterface;
use App\Manager\RevueManagement\ImageManager;
use App\Repository\RevueManagement\ImageRepository;
use App\Repository\RevueManagement\NumeroRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
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
class _TextToBlocksTextCommand extends Command
{
    protected static $defaultName = 'app:_text-to-blocksText';
    protected static $defaultDescription = 'transfert the text-to-blocksText';

    private $entityManager;
    private $kernel;
    private $slugger;
    private $notifier;
    private $workflow;
    private $imageManager;
    private $imageRepository;
    private $numeroRepository;
    private $params;
    private $mailer;
    private $twig;
    private $translator;

    public function __construct(EntityManagerInterface $entityManager, ImageRepository $imageRepository, NumeroRepository $numeroRepository, ImageManager $imageManager, WorkflowInterface $numeroStateMachine, ParameterBagInterface $params, NotifierInterface $notifier, KernelInterface $kernel, SluggerInterface $slugger, MailerInterface $mailer, Environment $twig, TranslatorInterface $translator)
    {
        $this->params = $params;
        $this->slugger = $slugger;
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

        $numeros = $this->numeroRepository->findBy(['isDeleted' => false], ['updateDate' => 'ASC']);

        foreach ($numeros as $numero) {
            $io->note(sprintf('Numero: %s', $numero->getTitle()));

            $images = $this->imageRepository->findBy(['numero' => $numero, 'isDeleted' => false]);

            if (\count($images) > 0) {
                $io->note(sprintf('images: %s', \count($images)));
                foreach ($images as $image) {
                    foreach ($image->getPages() as $page) {
                        $blocksText = '';
                        if (empty($page->getBlocksText())) {
                            $io->note(sprintf('page blocksText empty'));
                            foreach ($page->getBlocks() as $block) {
                                $blocksText .= $block->getText();
                            }
                            $page->setBlocksText($blocksText);
                            $this->entityManager->persist($page);
                            try {
                                $this->entityManager->flush();
                            } catch (DBALException $exception) {
                                $io->note($exception->getMessage());
                            } catch (\Throwable $exception) {
                                $io->note($exception->getMessage());
                            }
                        }
                    }
                }
            }
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
