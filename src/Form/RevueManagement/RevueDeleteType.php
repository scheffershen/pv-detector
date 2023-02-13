<?php

namespace App\Form\RevueManagement;

use Symfony\Component\Form\FormBuilderInterface;

class RevueDeleteType extends RevueType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('title')
            ->remove('editeur')
            ->remove('access')
            ->remove('endDate')
            ->remove('site')
            ->remove('periodicity')
            ->remove('isCFC')
            ->remove('contact')
            ; 
    }
}
