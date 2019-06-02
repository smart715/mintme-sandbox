<?php declare(strict_types = 1);

namespace App\Utils\Validator;

interface ValidatorInterface
{
    public function validate(): bool;
}
