<?php declare(strict_types = 1);

namespace App\Utils\Validator;

class SolAddressValidator implements ValidatorInterface
{
    /** @var string */
    private $address;

    /** @var string */
    private $message = 'Invalid SOL address';

    public function __construct(string $address)
    {
        $this->address = $address;
    }

    public function validate(): bool
    {
        return strlen($this->address) >= 32 && strlen($this->address) <= 44;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
