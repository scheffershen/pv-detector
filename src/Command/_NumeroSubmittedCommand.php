<?php

namespace App\Command;

use App\Manager\RevueManagement\ImageManager;
use App\Manager\RevueManagement\NumeroManager;
use App\Message\RevueManagement\NumeroMessage;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\RevueManagement\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @codeCoverageIgnore
 */
class _NumeroSubmittedCommand extends Command
{
    protected static $defaultName = 'app:_numero-submitted';
    protected static $defaultDescription = 'processing all numero "submitted" state';

    private $entityManager;
    private $messageBus;
    private $workflow;
    private $numeroRepository;
    private $numeroManager;
    private $imageManager;
    private $pageRepository;

    public function __construct(EntityManagerInterface $entityManager, NumeroRepository $numeroRepository, PageRepository $pageRepository, NumeroManager $numeroManager, ImageManager $imageManager, MessageBusInterface $bus, WorkflowInterface $numeroStateMachine)
    {
        $this->messageBus = $bus;
        $this->workflow = $numeroStateMachine;
        $this->numeroManager = $numeroManager;
        $this->imageManager = $imageManager;
        $this->pageRepository = $pageRepository;
        $this->numeroRepository = $numeroRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
        ;
    }

    /**
     * for dev.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $numeros = $this->numeroRepository->findBy(['isDeleted' => false, 'state' => 'submitted'], ['updateDate' => 'ASC']);

        foreach ($numeros as $numero) {
            $io->note(sprintf('Numero: %s', $numero->getTitle()));

            if ($this->workflow->can($numero, 'submit')) {
                if ($numero->isImage()) {
                    foreach ($numero->getImages() as $image) {
                        try {
                            //$this->imageManager->imageToText($image);
                        } catch (\Throwable $exception) {
                            //throw new \Exception($exception->getMessage());
                        }
                    }
                } else {
                    try {
                        //$this->numeroManager->pdfToText($numero);
                    } catch (\Throwable $exception) {
                        //throw new \Exception($exception->getMessage());
                    }
                }
                $this->workflow->apply($numero, 'submit');
                //$this->entityManager->flush();
                //$this->messageBus->dispatch(new NumeroMessage($numero));
            }
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
