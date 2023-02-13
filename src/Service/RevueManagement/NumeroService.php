<?php

declare(strict_types=1);

namespace App\Service\RevueManagement;

use App\Entity\RevueManagement\Image;
use App\Entity\RevueManagement\Numero;
use App\Manager\RevueManagement\ImageManager;
use App\Manager\RevueManagement\ZipManager;
use App\Repository\RevueManagement\ImageRepository;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\RevueManagement\PageRepository;
use App\Service\AbstractService;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NumeroService extends AbstractService
{
    private $em;
    private $translator;
    private $zipManager;
    private $slugger;
    private $security;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        ContainerInterface $container,
        KernelInterface $kernel,
        ZipManager $zipManager,
        SluggerInterface $slugger,
        TranslatorInterface $translator
    ) {
        parent::__construct($container);
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;
        $this->zipManager = $zipManager;
        $this->slugger = $slugger;
        $this->translator = $translator;
    }  

    public function create(FormInterface &$form, Numero &$numero): bool
    {
        return $this->process("create", $form, $numero, '');
    }

    public function update(FormInterface &$form, Numero &$numero, string $old_numero): bool
    {
        return $this->process("update", $form, $numero, $old_numero);
    }

    private function process(string $action, FormInterface &$form, Numero &$numero, string $old_numero): bool
    {
        $form_passed = true;
        $file_changed = false;
        $ds = DIRECTORY_SEPARATOR;
        $data_path = $this->kernel->getProjectDir(). $ds .'data'. $ds . 'revues'. $ds;

        if ($numero->isImage()) { // if Images
            $uploadFile = $form->get('imagesZip')->getData();
            if ($uploadFile) { // if a Zip of images
                $originalFilename = pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $uploadFile->guessExtension();
                $safeFilename = $this->slugger->slug($originalFilename) . '-' . date("YmdHi");
                try {
                    $uploadFile->move($data_path, $safeFilename.'.'.$extension);
                    if (!file_exists($data_path.$safeFilename)) {
                        mkdir($data_path . $safeFilename, 0777, true);
                    }
                    $_images = $this->zipManager->extractArchive($data_path.$safeFilename, $data_path.$safeFilename.'.'.$extension);
                    $num_page = 1;
                    // if update, remove old images
                    if ("update" === $action) { 
                        $this->removeNumeroOldImages($numero);
                    }
                    foreach ($_images as $_image) {
                        $image = new Image();                            
                        $image->setFileUri($safeFilename. $ds . $_image);
                        $image->setNumero($numero);
                        list($width, $height) = getimagesize($data_path . $safeFilename. $ds . $_image);
                        $image->setWidth($width);
                        $image->setHeight($height);
                        $image->setNumeroPage($num_page);
                        $this->entityManager->persist($image);
                        ++$num_page;
                    }
                    $file_changed = true;
                    $numero->setState('submitted');
                    $numero->setIsIndexed(false);
                } catch (FileException $e) {
                    $this->addFlash('error', $e->getMessage());
                    $form_passed = false;
                }
            } else {
                $num_page = 1;                
                $destinationDir = $this->slugger->slug($numero->getTitle() . '_'. $numero->getNumero()) . '-' . date("YmdHi");
                if (!file_exists($data_path.$destinationDir)) {
                    mkdir($data_path.$destinationDir, 0777, true);
                }               
                
                foreach ($numero->getImages() as $key => $image) {
                    if (UPLOAD_ERR_OK == $_FILES['numero']['error']['images'][$key]['upload']) {
                        $pieces = explode('.', $_FILES['numero']['name']['images'][$key]['upload']);
                        $safeFilename = $this->slugger->slug($pieces[0]).'.'.$pieces[1];
                        if ($_FILES['numero']['tmp_name']['images'][$key]['upload']) {
                            move_uploaded_file($_FILES['numero']['tmp_name']['images'][$key]['upload'], $data_path. $destinationDir. $ds . $safeFilename);
                            $image->setFileUri($destinationDir. $ds . $safeFilename);
                            $image->setNumero($numero);
                            list($width, $height) = getimagesize($data_path . $destinationDir. $ds . $safeFilename);
                            $image->setWidth($width);
                            $image->setHeight($height);
                            $image->setNumeroPage($num_page);
                            $this->entityManager->persist($image);
                            ++$num_page;
                        } else {
                            $form->addError(new FormError($this->translator->trans('numero.image_missing')));
                            $form_passed = false;
                            break;
                        }
                        $file_changed = true;
                        $numero->setState('submitted');
                        $numero->setIsIndexed(false);                        
                    } else {
                        $form->addError(new FormError($_FILES['numero']['error']['images'][$key]['upload']));
                        $form_passed = false;
                        break;
                    }
                } 
            }
        } else { // if PDF
            $uploadFile = $form->get('file')->getData();
            if ($uploadFile) {
                $originalFilename = pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.date("YmdHi").'.'.$uploadFile->guessExtension();
                // if update, remove old images
                if ("update" === $action) { 
                    $this->removeNumeroOldImages($numero);
                }                                
                try {
                    $uploadFile->move($data_path, $newFilename);
                    $numero->setFileUri($newFilename);
                    $file_changed = true;
                    $numero->setState('submitted');
                    $numero->setIsIndexed(false);
                } catch (FileException $e) {
                    $this->addFlash('error', $e->getMessage());
                    $form_passed = false;
                }
            } elseif ("create" === $action) {
                $form->addError(new FormError($this->translator->trans('numero.pdf_missing')));
                $form_passed = false;
            }
        }

        if ($form_passed) {
            if ("create" === $action) { 
                $numero->setCreateUser($this->security->getUser());
            }
            if ($file_changed) {
                $numero->setIsIndexed(false);
            }            
            $numero->setUpdateUser($this->security->getUser());
			return $this->save($numero, $action);
        }
        return false;
    }

    private function save(Numero $numero, string $action): bool
    {
        try {
            $this->entityManager->persist($numero);
            $this->entityManager->flush();
            switch ($action) {
                case 'create':
                    $this->addFlash('success', $this->translator->trans('numero.flash.created'));
                    break;
                case 'update':
                    $this->addFlash('success', $this->translator->trans('numero.flash.updated'));
                    break;
                case 'remove':
                    $this->addFlash('success', $this->translator->trans('numero.flash.deleted'));
                    break;
            }

            return true;
        } catch (\Doctrine\DBAL\DBALException $exception) {
            $this->addFlash('error', $exception->getMessage());
        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return false;
    }  

    private function removeNumeroOldImages(Numero $numero): void 
    {
        if (0 < \count($numero->getImages()) ) {
            foreach ($numero->getImages() as $image) {
                if (!$image->getIsDeleted()) {
                    // detacher nomero à image
                    $image->setNumero(null)->setIsDeleted(true);
                    $this->entityManager->persist($image);
                    // remove indexation
                }
            }
            $this->entityManager->flush();
        }

        return; 
    }
}