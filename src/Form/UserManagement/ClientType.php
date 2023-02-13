<?php

namespace App\Form\UserManagement;

use App\Entity\UserManagement\Client;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('code')
            ->add('jourBilanHebdomadaire', EntityType::class, [
                'label' => 'lov.jour',
                'class' => 'App\Entity\LovManagement\Jour',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('j')
                        ->where('j.isValid = true')
                        ->orderBy('j.sort', 'ASC');
                },
                'attr' => ['class' => 'chosen'],
                'multiple' => false,
                'required' => true,
            ])   
            ->add('respondableClient', EntityType::class, [
                'label' => 'client.respondableClient',
                'class' => 'App\Entity\UserManagement\User',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.isEnable = true')
                        ->orderBy('u.firstname', 'ASC');
                },
                'choice_label' => function ($user) {
                    return $user->getFirstname() . ' ' . $user->getLastname();
                },
                'attr' => ['class' => 'chosen'],
                'multiple' => false,
                'required' => true,
            ])     
            ->add('backupResponsableClient', EntityType::class, [
                'label' => 'client.backupResponsableClient',
                'class' => 'App\Entity\UserManagement\User',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.isEnable = true')
                        ->orderBy('u.firstname', 'ASC');
                },
                'choice_label' => function ($user) {
                    return $user->getFirstname() . ' ' . $user->getLastname();
                },
                'attr' => ['class' => 'chosen'],
                'multiple' => false,
                'required' => false,
            ])                         
            ->add('liens', CollectionType::class, [
                'entry_type' => LienType::class,
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
