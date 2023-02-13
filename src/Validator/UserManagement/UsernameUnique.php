<?php

declare(strict_types=1);

namespace App\Validator\UserManagement;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UsernameUnique extends Constraint
{
    /**
     * @var string
     */
    public $message = 'username already taken';
}
