<?php

namespace App\Form\RevueManagement;

use App\Entity\RevueManagement\Revue;
use App\Form\RevueManagement\EventSubscriber\AddEndDateFieldSubscriber;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RevueType extends AbstractType
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'label' => 'revue.title',
            ])
            ->add('editeur', null, [
                'label' => 'revue.editeur',
            ])
            ->add('endDate', DateType::class, [
                    'required' => true,
                    'html5' => false,
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'help' => 'dd/mm/yyyy',
                    'attr' => ['class' => 'pop-date'],
                    'label' => 'revue.endDate',
            ])
            ->add('access', EntityType::class, [
                'label' => 'lov.access',
                'class' => 'App\Entity\LovManagement\Access',
                'choice_label' => 'title',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.isValid = true')
                        ->orderBy('c.sort', 'ASC');
                },
                'attr' => ['class' => 'chosen'],
                'required' => true,
            ])
            ->add('site', null, [
                'label' => 'revue.site',
            ])
            ->add('periodicity', null, [
                'label' => 'revue.periodicity',
            ])
            ->add('isCFC', null, [
                'label' => 'revue.isCFC',
                'required' => false,                
            ])
            ->add('clients', EntityType::class, [
                'label' => 'revue.clients',
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
            ->add('contact', null, [
                'label' => 'revue.contact',
            ]);


        $builder->addEventSubscriber(new AddEndDateFieldSubscriber($this->requestStack));        
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Revue::class,
        ]);
    }
}
