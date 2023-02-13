<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecurityAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class DefaultController extends AbstractController
{
    /**
     * home page.
     *
     * @Route("/", name="home", methods="GET")
     */
    public function index(Request $request, Security $security): Response
    {
        if ($security->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->redirectToRoute('app_login');
    }

    /**
     * changeLocale.
     *
     * @Route("/changelocal", name="change_locale", methods="GET")
     */
    public function changeLocale(Request $request, $_locale): Response
    {
        //$request = $request();
        $url = $request->query->get('_route') ?: 'home';

        $param = $request->query->get('_route_params');
        $param['_locale'] = $_locale;

        if ('app_login' === $url) {
            $param['change_locale'] = 1;
        }

        switch ($_locale) {
            case 'fr':
                $this->get('session')->set('_locale', 'en');
                $request->setLocale('en');
                break;
            case 'en':
                $this->get('session')->set('_locale', 'fr');
                $request->setLocale('fr');
                break;
            default:
                $this->get('session')->set('_locale', 'fr');
                $request->setLocale('fr');
                break;
        }

        return $this->redirectToRoute($url, $param);
    }

    /**
     * return a security private upload.
     *
     * @Route("/upload/{format}/{upload}/{dir}", defaults={"format": "origin", "dir": ""}, name="admin_private_upload", methods="GET")
     * @SecurityAccess("is_granted('ROLE_USER')")
     */
    public function upload(Request $request, string $dir = '', string $upload, string $format = 'origin', KernelInterface $kernel): Response
    {
        $fs = new Filesystem();

        if ('' == $dir) {
            if ('origin' == $format) {
                $filePath = $kernel->getProjectDir().'/data/'.$upload;
            } else {
                $filePath = $kernel->getProjectDir().'/data/'.$format.'/'.$upload;
            }
        } else {
            $filePath = $kernel->getProjectDir().'/data/'.$format.'/'.$dir.'/'.$upload;
        }

        if ($fs->exists($filePath)) {
            $response = new BinaryFileResponse($filePath);
            $response->headers->set('Content-Type', mime_content_type($filePath));
            $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_INLINE,
                    basename($filePath)
                );

            return $response;
        }

        $filePath = $kernel->getProjectDir().'/data/not-found.png';
        if ($fs->exists($filePath)) {
            $response = new BinaryFileResponse($filePath);
            $response->headers->set('Content-Type', mime_content_type($filePath));
            $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_INLINE,
                    basename($filePath)
                );

            return $response;
        }
        throw new FileNotFoundException($filePath);
    }
}
