<?php

namespace App\Form\RevueManagement;

use Symfony\Component\Form\FormBuilderInterface;

class NumeroDeleteType extends NumeroType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('title')
            ->remove('numero')
            ->remove('revue')
            ->remove('receiptDate')
            ->remove('file')
            ->remove('isImage')
            ->remove('images')
            ; 
    }
}
