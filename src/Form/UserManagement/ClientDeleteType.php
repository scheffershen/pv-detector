<?php

namespace App\Form\UserManagement;

use Symfony\Component\Form\FormBuilderInterface;

class ClientDeleteType extends ClientType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('name')
            ->remove('jourBilanHebdomadaire')
            ->remove('respondableClient')
            ->remove('backupResponsableClient')
            ->remove('liens')
            ;        
    }
}
