<?php

namespace App\Form\UserManagement;

use Symfony\Component\Form\FormBuilderInterface;

class ClientEditType extends ClientType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
    }
}
