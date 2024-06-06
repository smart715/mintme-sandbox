<?php declare(strict_types = 1);

namespace App\Utils\Validator;

interface ValidatorInterface
{
    /** @throws \Exception */
    public function validate(): bool;
    public function getMessage(): string;
}
