<?php

namespace App\Validator\UserManagement;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ComplexPasswordValidator extends ConstraintValidator
{
    /**
     * @param string $value
     * @param Constraint $constraint
     * il n'est pas possible de mettre un caractère spécial (ex : ! ou ? ...) dans la création / mise à jour de mot de pass.
     * Mot de passe – Obligatoire – Avec confirmation obligatoire, 8 caractères minimum et au moins un chiffre, une majuscule et une minuscule,
     * Admin123!*@#$%^&+=
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value) {
            if (!preg_match('/[0-9]/', $value, $matches) || // number
                !preg_match('/[a-z]/', $value, $matches) || // lowercase character
                !preg_match('/[A-Z]/', $value, $matches)    // uppercase character
            ) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}