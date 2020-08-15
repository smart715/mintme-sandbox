<?php declare(strict_types = 1);

namespace App\Utils\Validator;

class EthereumAddressValidator implements ValidatorInterface
{
    /** @var string */
    private $address;

    /** @var string */
    private $message = 'Invalid ethereum address';

    public function __construct(string $address)
    {
        $this->address = $address;
    }

    public function validate(): bool
    {
        return 0 === strpos($this->address, '0x') && 42 === strlen($this->address);
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
