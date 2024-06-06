<?php declare(strict_types = 1);

namespace App\Wallet\Exception;

/** @codeCoverageIgnore */
class IncorrectAddressException extends \Exception
{
    public function __construct(string $errorMsg, string $address)
    {
        parent::__construct($errorMsg . ". Address: $address");
    }
}
