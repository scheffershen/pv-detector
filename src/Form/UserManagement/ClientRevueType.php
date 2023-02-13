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

class ClientRevueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder   
            ->add('revues', EntityType::class, [
                'label' => 'client.revues',
                'class' => 'App\Entity\RevueManagement\Revue',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('r')
                        ->where('r.isValid = true')
                        ->orderBy('r.title', 'ASC');
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
            'data_class' => Client::class,
            'allow_extra_fields' => true
        ]);
    }
}
