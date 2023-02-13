<?php

namespace App\Form\SearchManagement;

use App\Entity\SearchManagement\Dci;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DciType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, [
                'label' => 'dci.title'
            ])
            ->add('categorie', null, [
                'label' => 'dci.categorie'
            ])
            ->add('description', null, [
                'label' => 'dci.description'
            ])
            ->add('clients', EntityType::class, [
                'label' => 'dci.clients',
                'class' => 'App\Entity\UserManagement\Client',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.isValid = true')
                        ->orderBy('c.name', 'ASC');
                },
                'attr' => ['class' => 'js-select2'],
                'multiple' => true,
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Dci::class,
        ]);
    }
}
