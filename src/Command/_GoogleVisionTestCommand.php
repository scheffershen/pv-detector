<?php

namespace App\Command;

use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\ImageAnnotatorClient as GoogleImageAnnotatorClient;
use Google\Cloud\Vision\V1\Likelihood;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @codeCoverageIgnore
 */
class _GoogleVisionTestCommand extends Command
{
    protected static $defaultName = 'app:_google-vision-test';
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
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
        ;
    }

    /**
     * for dev
     * php bin/console app:google-vision-test 9.png.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
            $filePath = $this->kernel->getProjectDir().'/data/revues/'.$arg1;
            $fs = new Filesystem();
            if ($fs->exists($filePath)) {
                // // test2: php bin/console app:google-vision-test plan.jpg
                $imageAnnotator = new GoogleImageAnnotatorClient(['credentials' => $this->keyFilePath]);
                $image = file_get_contents($filePath);
                $response = $imageAnnotator->documentTextDetection($image);
                $annotation = $response->getFullTextAnnotation();

                // print out detailed and structured information about document text
                if ($annotation) {
                    foreach ($annotation->getPages() as $page) {
                        foreach ($page->getBlocks() as $block) {
                            $block_text = '';
                            foreach ($block->getParagraphs() as $paragraph) {
                                foreach ($paragraph->getWords() as $word) {
                                    foreach ($word->getSymbols() as $symbol) {
                                        $block_text .= $symbol->getText();
                                    }
                                    $block_text .= ' ';
                                }
                                $block_text .= "\n";
                            }
                            printf('Block content: %s', $block_text);
                            printf('Block confidence: %f'.PHP_EOL,
                                $block->getConfidence());

                            // get bounds
                            $vertices = $block->getBoundingBox()->getVertices();
                            $bounds = [];
                            foreach ($vertices as $vertex) {
                                $bounds[] = sprintf('(%d,%d)', $vertex->getX(),
                                    $vertex->getY());
                            }
                            echo 'Bounds: '.join(', ', $bounds).PHP_EOL;
                            echo PHP_EOL;
                        }
                    }
                } else {
                    echo 'No text found'.PHP_EOL;
                }

                $imageAnnotator->close();

            // // test1: php bin/console app:google-vision-test family-photo.jpg
                // $client = new GoogleImageAnnotatorClient(['credentials' => $this->keyFilePath]);
                // $familyPhotoResource = fopen($filePath, 'r');
                // // Annotate an image, detecting faces.
                // $annotation = $client->annotateImage(
                //     $familyPhotoResource,
                //     [Type::FACE_DETECTION]
                // );

                // // Determine if the detected faces have headwear.
                // foreach ($annotation->getFaceAnnotations() as $faceAnnotation) {
                //     $likelihood = Likelihood::name($faceAnnotation->getHeadwearLikelihood());
                //     echo "Likelihood of headwear: $likelihood" . PHP_EOL;
                // }
            } else {
                throw new FileNotFoundException($filePath);
            }
        }

        $io->success('End');

        return Command::SUCCESS;
    }
}
