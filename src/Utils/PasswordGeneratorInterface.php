<?php

declare(strict_types=1);

namespace App\Utils;

interface PasswordGeneratorInterface
{
    public function generatePassword(): string;
}
