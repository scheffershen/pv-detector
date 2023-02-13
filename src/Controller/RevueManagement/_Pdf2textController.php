<?php

namespace App\Controller\RevueManagement;

use App\Entity\RevueManagement\Numero;
use App\Entity\RevueManagement\Page;
use App\Form\RevueManagement\NumeroType;
use App\Utils\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Smalot\PdfParser\Parser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @codeCoverageIgnore
 * @Route("/admin/pdf2text")
 * for dev
 */
class _Pdf2textController extends AbstractController
{
    private $kernel;
    private $slugger;
    private $translator;

    public function __construct(TranslatorInterface $translator, KernelInterface $kernel, SluggerInterface $slugger)
    {
        $this->kernel = $kernel;
        $this->slugger = $slugger;
        $this->translator = $translator;
    }

    /**
     * @Route("/test1", name="admin_pdf2text_test1", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     * not useful
     */
    public function test1(Request $request): Response
    {
        $numero = new Numero();
        $form = $this->createForm(NumeroType::class, $numero);
        $form->handleRequest($request);
        $p = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadFile = $form->get('file')->getData();
            if ($uploadFile) {
                $originalFilename = pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadFile->guessExtension();
                $uploadFile->move($this->kernel->getProjectDir().'/data/revues/', $newFilename);
                $numero->setFileUri($newFilename);
                $parser = new Parser();
                $url = $this->kernel->getProjectDir().'/data/revues/'.$numero->getFileUri();
                $pdf = $parser->parseFile($url);
                $pages = $pdf->getPages();
                $num_page = 1;
                foreach ($pages as $page) {
                    //$p = new Page();
                    $escape_content = Utils::clean_text($page->getText(), ['TOUT']);
                    $escape_content = preg_replace('/[\x00-\x1F\x7F]/u', '', $escape_content);
                    $escape_content = str_replace(["\r\n", "\n"], '<br/>', $escape_content);
                    $escape_content = str_replace('\\', '', $escape_content);
                    $p[] = $page->getText(); //$escape_content;
                    // $p->setContent($escape_content);
                    // $p->setNumeroPage($num_page);
                    // $p->setNumero($numero);
                }
                $this->addFlash('success', $this->translator->trans('numero.flash.created'));
            } else {
                $form->addError(new FormError($this->translator->trans('numero.pdf_missing')));
            }
        }

        return $this->render('RevueManagement/Numero/pdf2text.html.twig', [
            'numero' => $numero,
            'pages' => $p,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/test2", name="admin_pdf2text_test2", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     * not useful
     */
    public function test2(Request $request): Response
    {
        $numero = new Numero();
        $form = $this->createForm(NumeroType::class, $numero);
        $form->handleRequest($request);
        $p = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadFile = $form->get('file')->getData();
            if ($uploadFile) {
                $originalFilename = pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadFile->guessExtension();
                $uploadFile->move($this->kernel->getProjectDir().'/data/revues/', $newFilename);
                $numero->setFileUri($newFilename);
                $parser = new Parser();
                $url = $this->kernel->getProjectDir().'/data/revues/'.$numero->getFileUri();
                $pdf = $parser->parseFile($url);
                $pages = $pdf->getPages();
                $num_page = 1;
                foreach ($pages as $page) {
                    //$p = new Page();
                    $escape_content = Utils::clean_text($page->getText(), ['TOUT']);
                    $escape_content = preg_replace('/[\x00-\x1F\x7F]/u', '', $escape_content);
                    $escape_content = str_replace(["\r\n", "\n"], '<br/>', $escape_content);
                    $escape_content = str_replace('\\', '', $escape_content);
                    $p[] = $escape_content;
                    // $p->setContent($escape_content);
                    // $p->setNumeroPage($num_page);
                    // $p->setNumero($numero);
                }
                $this->addFlash('success', $this->translator->trans('numero.flash.created'));
            } else {
                $form->addError(new FormError($this->translator->trans('numero.pdf_missing')));
            }
        }

        return $this->render('RevueManagement/Numero/pdf2text.html.twig', [
            'numero' => $numero,
            'pages' => $p,
            'form' => $form->createView(),
        ]);
    }
}
