<?php

namespace App\Form\RevueManagement;

use App\Entity\RevueManagement\Numero;
use App\Form\RevueManagement\EventSubscriber\AddReceiptDateFieldSubscriber;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class NumeroType extends AbstractType
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $request = $this->requestStack->getCurrentRequest();

        $builder->add('title', null, [
                'label' => 'numero.reference',
            ])
            ->add('numero', null, [
                'label' => 'numero.numero',
            ])
            ->add('receiptDate', DateType::class, [
                    'required' => true,
                    'html5' => false,
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd',
                    'help' => 'yyyy-mm-dd',
                    'label' => 'numero.receiptDate',
            ])
            ->add('revue', EntityType::class, [
                'label' => 'numero.revue',
                'class' => 'App\Entity\RevueManagement\Revue',
                'choice_label' => 'title',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.isValid = true')
                        ->orderBy('c.title', 'ASC');
                },
                'attr' => ['class' => 'js-select2'],
                'required' => true,
            ])
            ->add('file', FileType::class, [
                'label' => 'numero.file',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control check-mime ',
                    'data-mime' => 'application/pdf',
                ],
                'help' => 'Format .pdf',
            ])
            ->add('isImage', CheckboxType::class, [
                'label' => 'numero.isImage',
                'required' => false,
            ])
            ->add('images', CollectionType::class, [
                'entry_type' => ImageType::class,
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('imagesZip', FileType::class, [
                'required' => false,
                'help' => 'Format .zip',
                'constraints' => [
                        new File([
                            'mimeTypes' => [
                                'application/zip',
                                'application/octet-stream',
                                'application/x-zip-compressed',
                                'multipart/x-zip',
                            ],
                            'mimeTypesMessage' => 'Veuillez télécharger un fichier Zip '
                        ])
                    ],
                'help' => 'Format .zip',    
            ])          
        ;

        $builder->addEventSubscriber(new AddReceiptDateFieldSubscriber($this->requestStack));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Numero::class,
        ]);
    }
}
