<?php

namespace App\Form\RapportManagement;

use Symfony\Component\Form\FormBuilderInterface;

class RapportDeleteType extends RapportType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('commentaire')
            //->remove('numero')
            ;        
    }
}
