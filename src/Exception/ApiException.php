<?php declare(strict_types = 1);

namespace App\Exception;

/** @codeCoverageIgnore  */
abstract class ApiException extends \Exception implements ApiExceptionInterface
{
    abstract public function getStatusCode(): int;

    public function getData(): array
    {
        return ['message' => $this->getMessage()];
    }
}
