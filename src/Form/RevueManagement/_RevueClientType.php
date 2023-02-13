<?php

namespace App\Form\RevueManagement;

use App\Entity\RevueManagement\Revue;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class _RevueClientType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder   
            ->add('clients', EntityType::class, [
                'label' => 'revue.clients',
                'class' => 'App\Entity\UserManagement\Client',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.isValid = true')
                        ->orderBy('c.name', 'ASC');
                },
                'translation_domain' => 'messages',
                'expanded' => true,
                'multiple' => true,
                'required' => true,  
            ])
        ;       
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Revue::class,
            'allow_extra_fields' => true
        ]);
    }
}
