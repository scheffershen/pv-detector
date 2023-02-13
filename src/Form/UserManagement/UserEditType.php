<?php

namespace App\Form\UserManagement;

use Symfony\Component\Form\FormBuilderInterface;

class UserEditType extends UserType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
    }
}
