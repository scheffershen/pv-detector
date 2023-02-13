<?php

declare(strict_types=1);

namespace App\Validator\UserManagement;

use App\Repository\UserManagement\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class UsernameUniqueValidator extends ConstraintValidator
{
    private $userRepository;

    public function __construct(UserRepository $repository)
    {
        $this->userRepository = $repository;
    }

    public function validate($value, Constraint $constraint): void
    {
        /* @var $constraint \App\Validator\UsernameUnique */

        if (null === $value || '' === $value) {
            return;
        }

        $existingUser = $this->userRepository->findOneBy(['username' => $value]);
        
        $data = $this->context->getRoot()->getData();

        if ($data->getId() && $existingUser && $existingUser->getId() !== $data->getId() ) { // edit action
            $this->context->buildViolation($constraint->message)
                    ->addViolation();
        } elseif (!$data->getId() && $existingUser) { // add action
            $this->context->buildViolation($constraint->message)
                    ->addViolation();
        }

    }  
}
