<?php

namespace App\Command;

use Google\Cloud\Core\ServiceBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @codeCoverageIgnore
 */
class _GoogleNaturalLanguageTestCommand extends Command
{
    protected static $defaultName = 'app:_google-natural-language-test';
    protected static $defaultDescription = 'Add a short description for your command';

    private $kernel;
    private $keyFilePath;

    public function __construct(string $keyFilePath, KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->keyFilePath = $keyFilePath;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
        ;
    }

    /**
     * for dev
     * php bin/console app:google-natural-language-test.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cloud = new ServiceBuilder(['keyFile' => $this->keyFilePath]);
        $language = $cloud->language();

        // Analyze a sentence.
        $annotation = $language->annotateText("ors de cette édition de l'ASCO® , les cancers urolo giques ont une nouvelle fois été à l'honneur , avec
2 présentations ( sur 5 ) en session présidentielle . Pour les cancers de la prostate métastatiques résistant à la castration , les résultats de l'étude de phase III VISION ont constitué l'élément marquant en montrant un bénéfice en survie sans progression radiographique , mais aussi en survie globale , apporté par une radio thérapie métabolique ciblant le PSMA . Ces résultats ouvrent de nouvelles perspectives thérapeutiques . Il faudra que la France comble rapidement le retard qu'elle a pris sur l'Allemagne et les pays du nord de l'Europe dans le développement des PET - scan au PSMA pour remplacer celui à la choline ... Dans un monde marqué par les problèmes des minorités et des communautés , plusieurs communications se sont focalisées sur les spécificités génomiques des cancers de la prostate des Afro - Américains et sur leurs diffi cultés d'accès au diagnostic précoce et à l'innovation thérapeutique .");

        // Check the sentiment.
        if ($annotation->sentiment() > 0) {
            $io->note(sprintf(' %s', 'This is a positive message.'));
        }

        // Detect entities.
        $entities = $annotation->entitiesByType('LOCATION');

        foreach ($entities as $entity) {
            $io->note(sprintf(' %s', $entity['name']));
        }

        // Parse the syntax.
        $tokens = $annotation->tokensByTag('NOUN');

        foreach ($tokens as $token) {
            $io->note(sprintf(' %s', $token['text']['content']));
        }

        $io->success('End');

        return Command::SUCCESS;
    }
}
