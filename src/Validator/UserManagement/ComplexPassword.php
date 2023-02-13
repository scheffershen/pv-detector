<?php

namespace App\Validator\UserManagement;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ComplexPassword extends Constraint
{
    public $message = 'Le mot de passe doit contenir au moins un chiffre, un caractère minuscule et un caractère majuscule, et minimum 8 caractères';
}