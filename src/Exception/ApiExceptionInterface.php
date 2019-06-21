<?php declare(strict_types = 1);

namespace App\Exception;

interface ApiExceptionInterface
{
    public function getData(): array;
    public function getStatusCode(): int;
}
