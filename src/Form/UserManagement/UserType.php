<?php

namespace App\Form\UserManagement;

use App\Entity\UserManagement\User;
use App\Form\UserManagement\EventSubscriber\AddRolesFieldSubscriber;
use App\Form\UserManagement\EventSubscriber\UpdateRolesFieldSubscriber;
use App\Validator\UserManagement\UsernameUnique;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', null, [
                'label' => 'label.username',
                'constraints' => [new Length(['min' => 2]), new UsernameUnique()],
            ])
            ->add('email', null, [
                'label' => 'label.email',
            ])
            ->add('firstname', null, [
                'attr' => [
                    'autofocus' => true,
                ],
                'required' => true,
                'label' => 'label.firstname',
            ])
            ->add('lastname', null, [
                'attr' => [
                    'autofocus' => true,
                ],
                'required' => true,
                'label' => 'label.lastname',
            ])           
            ->add('isEnable', CheckboxType::class, [
                    'label' => 'user.isEnable',
                    'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                //'attr' => ['class' => 'roles'],
                'choices'  => USER::ROLES,
            ])              
            ->add('clients', EntityType::class, [
                'label' => 'user.client',
                'class' => 'App\Entity\UserManagement\Client',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.isValid = true')
                        ->orderBy('c.name', 'ASC');
                },
                'attr' => ['class' => 'js-select2'],
                'multiple' => true,
                'required' => false,
            ])            
            ;

        //$builder->addEventSubscriber(new AddRolesFieldSubscriber())
        //    ->get('roles')->addEventSubscriber(new UpdateRolesFieldSubscriber());            
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
