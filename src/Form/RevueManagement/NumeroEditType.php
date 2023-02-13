<?php

namespace App\Form\RevueManagement;

use App\Entity\RevueManagement\Numero;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class NumeroEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('receiptDate', TextType::class, [
                    'required' => true, ])
            ->add('revue', EntityType::class, [
                'label' => 'revue.title',
                'class' => 'App\Entity\RevueManagement\Revue',
                'choice_label' => 'title',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.isValid = true')
                        ->orderBy('c.title', 'ASC');
                },
                'attr' => ['class' => 'chosen'],
                'placeholder' => '-',
                'required' => true,
            ])
            ->add('file', VichFileType::class, [
                'required' => false,
                'mapped' => false,
                'allow_delete' => false,
                'download_uri' => false,
                'download_label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Numero::class,
        ]);
    }
}
