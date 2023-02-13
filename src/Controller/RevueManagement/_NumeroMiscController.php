<?php

namespace App\Controller\RevueManagement;

use App\Entity\RevueManagement\Image;
use App\Entity\RevueManagement\Numero;
use App\Manager\RevueManagement\ImageManager;
use App\Message\RevueManagement\NumeroMessage;
use App\Repository\RevueManagement\ImageRepository;
use App\Repository\RevueManagement\NumeroRepository;
use DonatelloZa\RakePlus\RakePlus;
use function Rap2hpoutre\RemoveStopWords\remove_stop_words;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Spatie\PdfToImage\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TextAnalysis\Collocations\CollocationFinder;
use TextAnalysis\Documents\TokensDocument;
use TextAnalysis\Filters\CharFilter;
use TextAnalysis\Filters\LowerCaseFilter;
use TextAnalysis\Filters\PunctuationFilter;
use TextAnalysis\Filters\QuotesFilter;
use TextAnalysis\Filters\SpacePunctuationFilter;
use TextAnalysis\Filters\StopWordsFilter;
use TextAnalysis\Taggers\StanfordNerTagger;
use TextAnalysis\Tokenizers\GeneralTokenizer;
use TextAnalysis\Tokenizers\WhitespaceTokenizer;

/**
 * @Route("/admin/numero")
 */
class _NumeroMiscController extends AbstractController
{
    private $kernel;
    private $messageBus;
    private $slugger;
    private $translator;
    private $imageManager;
    private $imageRepository;
    private $numeroRepository;
    private $serializer;

    public function __construct(MessageBusInterface $messageBus, TranslatorInterface $translator, KernelInterface $kernel, SluggerInterface $slugger, ImageManager $imageManager, ImageRepository $imageRepository, NumeroRepository $numeroRepository, SerializerInterface $serializer)
    {
        $this->kernel = $kernel;
        $this->messageBus = $messageBus;
        $this->slugger = $slugger;
        $this->translator = $translator;
        $this->imageManager = $imageManager;
        $this->imageRepository = $imageRepository;
        $this->numeroRepository = $numeroRepository;
        $this->serializer = $serializer;
    }

    /**
     * @codeCoverageIgnore
     * @Route("/pages_no_stop_words/{id}", name="admin_numero_no_stop_words", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function noStopWords(Request $request, Numero $numero): Response
    {
        $pages = [];
        $nb_page = 0;
        foreach ($numero->getImages() as $image) {
            if (!$image->isDeleted()) {
                foreach ($image->getPages() as $page) {
                    if (!$page->isDeleted()) {
                        $pages[$nb_page++] = remove_stop_words($page->getBlocksText(), 'fr');
                    }
                    ++$nb_page;
                }
            }
        }

        return $this->render('RevueManagement/Numero/pages_no_stop_words.html.twig', [
            'numero' => $numero,
            'pages' => $pages,
        ]);
    }

    /**
     * @codeCoverageIgnore
     * @Route("/phrases/{id}", name="admin_numero_phrases", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function phrases(Request $request, Numero $numero): Response
    {
        $content = '';
        foreach ($numero->getImages() as $image) {
            if (!$image->isDeleted()) {
                foreach ($image->getPages() as $page) {
                    if (!$page->isDeleted()) {
                        //$content .= remove_stop_words($page->getBlocksText(), 'fr'). PHP_EOL;
                        $content .= $page->getBlocksText().PHP_EOL;
                    }
                }
            }
        }
        // https://github.com/Donatello-za/rake-php-plus
        $rake = RakePlus::create($content, 'fr_FR');
        $phrase_scores = $rake->sortByScore('desc')->scores();

        return $this->render('RevueManagement/Numero/numero_phrases.html.twig', [
            'numero' => $numero,
            'phrases' => \array_slice($phrase_scores, 0, 500),
        ]);
    }

    /**
     * @codeCoverageIgnore
     * @Route("/collocation/{id}", name="admin_numero_collocation", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function collocation(Request $request, Numero $numero): Response
    {
        $content = '';
        foreach ($numero->getImages() as $image) {
            if (!$image->isDeleted()) {
                foreach ($image->getPages() as $page) {
                    if (!$page->isDeleted()) {
                        //$content .= remove_stop_words($page->getBlocksText(), 'fr'). PHP_EOL;
                        $content .= $page->getBlocksText().PHP_EOL;
                    }
                }
            }
        }

        $testData = (new SpacePunctuationFilter())->transform($content);
        $tokens = (new GeneralTokenizer(" \n\t\r"))->tokenize($testData);
        $tokenDoc = new TokensDocument($tokens);
        $tokenDoc->applyTransformation(new LowerCaseFilter())
                ->applyTransformation(new PunctuationFilter([]), false)
                ->applyTransformation(new StopWordsFilter([]))
                ->applyTransformation(new QuotesFilter())
                ->applyTransformation(new CharFilter());

        $finder = new CollocationFinder($tokenDoc->toArray(), 2);

        return $this->render('RevueManagement/Numero/numero_collocation.html.twig', [
            'numero' => $numero,
            'collocations' => $finder->getCollocationsByPmi(),
        ]);
    }

    /**
     * @codeCoverageIgnore
     * taggers/stanford-ner-2015-12-09 does not exist !
     * @Route("/entityExtraction/{id}", name="admin_numero_entityExtraction", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function entityExtraction(Request $request, Numero $numero): Response
    {
        $content = '';
        foreach ($numero->getImages() as $image) {
            if (!$image->isDeleted()) {
                foreach ($image->getPages() as $page) {
                    if (!$page->isDeleted()) {
                        //$content .= remove_stop_words($page->getBlocksText(), 'fr'). PHP_EOL;
                        $content .= $page->getBlocksText().PHP_EOL;
                    }
                }
            }
        }
        $document = new TokensDocument((new WhitespaceTokenizer())->tokenize($content));
        $tagger = new StanfordNerTagger();
        $output = $tagger->tag($document->getDocumentData());

        return $this->render('RevueManagement/Numero/numero_entityExtraction.html.twig', [
            'numero' => $numero,
            'output' => $output,
        ]);
    }

    /**
     * @codeCoverageIgnore
     * @Route("/keywords/{id}", name="admin_numero_keywords", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function keywords(Request $request, Numero $numero): Response
    {
        $content = '';
        foreach ($numero->getImages() as $image) {
            if (!$image->isDeleted()) {
                foreach ($image->getPages() as $page) {
                    if (!$page->isDeleted()) {
                        $content .= remove_stop_words($page->getBlocksText(), 'fr').PHP_EOL;
                        //$content .= $page->getBlocksText(). PHP_EOL;
                    }
                }
            }
        }
        // https://github.com/Donatello-za/rake-php-plus
        $keywords = RakePlus::create($content)->keywords();

        return $this->render('RevueManagement/Numero/numero_keywords.html.twig', [
            'numero' => $numero,
            'keywords' => \array_slice($keywords, 0, 500),
        ]);
    }

    /**
     * @codeCoverageIgnore
     * @Route("/yake/{id}", name="admin_numero_yake", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function yake(Request $request, Numero $numero): Response
    {
        $content = '';
        foreach ($numero->getImages() as $image) {
            if (!$image->isDeleted()) {
                foreach ($image->getPages() as $page) {
                    if (!$page->isDeleted()) {
                        $content .= remove_stop_words($page->getBlocksText(), 'fr').PHP_EOL;
                        //$content .= $page->getBlocksText(). PHP_EOL;
                    }
                }
            }
        }

        $url = 'http://yake:5000/yake/';
        $ch = curl_init($url);
        $data = [
            'language' => 'fr',
            'max_ngram_size' => 3,
            'number_of_keywords' => 150,
            'text' => $content,
        ];
        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $this->render('RevueManagement/Numero/numero_yake.html.twig', [
            'numero' => $numero,
            'keywords' => json_decode($result, true),
        ]);
    }

    /**
     * @codeCoverageIgnore
     * @Route("/parserPdf2images/{id}", name="admin_numero_parserPdf2images", methods={"GET"})
     * https://stackoverflow.com/questions/45538259/live-feedback-from-php-in-symfony
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function parserPdf2images(Request $request, Numero $numero): StreamedResponse
    {
        $em = $this->getDoctrine()->getManager();
        $images = $this->imageRepository->findBy(['numero' => $numero]);
        $imageRepository = $this->imageRepository;
        $kernel = $this->kernel;
        $imageManager = $this->imageManager;

        if (0 == \count($images)) {
            echo sprintf('Numero: %s', $numero->getTitle());

            $filePath = $this->kernel->getProjectDir().'/data/revues/'.$numero->getFileUri();

            $fs = new Filesystem();

            if ($fs->exists($filePath)) {
                $pdf = new Pdf($filePath);
                $pages = $pdf->getNumberOfPages();
                $num_page = 1;
                for ($i = 1; $i <= $pages; ++$i) {
                    echo 'Creating a temp image file for page #'.$i;
                    $tmpFile = tmpfile();
                    $tmpPath = stream_get_meta_data($tmpFile)['uri'];
                    echo 'temp image file: '.$tmpPath.' for page #'.$i;
                    $pdf->setPage($i);
                    $pdf->setOutputFormat('png');
                    $pdf->setCompressionQuality(85);
                    $pdf->saveImage($tmpPath);

                    $safeFilename = $this->kernel->getProjectDir().'/data/revues/'.basename($numero->getFileUri(), '.pdf').'/'.$i.'.png';

                    echo 'image file: '.$safeFilename.' for page #'.$i;
                    $fs->copy($tmpPath, $safeFilename, true);

                    $image = new Image();
                    $image->setFileUri(basename($numero->getFileUri(), '.pdf').'/'.$i.'.png');
                    $image->setNumero($numero);
                    $image->setNumeroPage($num_page);
                    list($width, $height) = getimagesize($safeFilename);
                    $image->setWidth($width);
                    $image->setHeight($height);
                    $em->persist($image);
                    $em->flush();

                    ++$num_page;

                    try {
                        $this->imageManager->imageToText($image);
                    } catch (\Throwable $exception) {
                        echo $exception->getMessage();
                    }
                }
            } else {
                //throw new FileNotFoundException($filePath);
                echo sprintf('FileNotFoundException: %s', $filePath);
            }
        }

        return new Response('OK');
    }

    /**
     * @codeCoverageIgnore
     * submit un numero
     * @Route("/submit/{id}", name="admin_numero_submit", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function submit(Numero $numero)
    {
        $this->messageBus->dispatch(new NumeroMessage($numero));
        $this->addFlash('success', $numero->getState());

        return $this->redirectToRoute('admin_numero_index');
    }

    /**
     * @codeCoverageIgnore
     * treatment un numero
     * @Route("/treatment/{id}", name="admin_numero_treatment", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function treatment(Numero $numero)
    {
        $this->messageBus->dispatch(new NumeroMessage($numero));
        $this->addFlash('success', $numero->getState());

        return $this->redirectToRoute('admin_numero_index');
    }
}
