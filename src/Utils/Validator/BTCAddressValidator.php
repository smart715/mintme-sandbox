<?php declare(strict_types = 1);

namespace App\Utils\Validator;

class BTCAddressValidator implements ValidatorInterface
{
    /** @var string */
    private $address;

    /** @var string */
    private $message = 'Invalid BTC address';

    public function __construct(string $address)
    {
        $this->address = $address;
    }

    public function validate(): bool
    {
        return strlen($this->address) >= 26 && strlen($this->address) <= 35;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
