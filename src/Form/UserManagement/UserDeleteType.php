<?php

namespace App\Form\UserManagement;

use Symfony\Component\Form\FormBuilderInterface;

class UserDeleteType extends UserType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('username')
            ->remove('email')
            ->remove('firstname')
            ->remove('lastname')
            ->remove('roles')
            ->remove('isEnable')
            ->remove('clients')
            ;        
    }
}
