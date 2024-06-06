<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Crypto;
use App\Utils\Symbols;

class AddressValidator implements ValidatorInterface
{
    private Crypto $cryptoNetwork;

    private string $address;

    private string $message;
    
    public function __construct(Crypto $cryptoNetwork, string $address)
    {
        $this->cryptoNetwork = $cryptoNetwork;
        $this->address = $address;
    }

    public function validate(): bool
    {
        $this->message = "Invalid {$this->cryptoNetwork->getSymbol()} address";

        switch ($this->cryptoNetwork->getSymbol()) {
            case Symbols::BTC:
                return (new BTCAddressValidator($this->address))->validate();
            case Symbols::SOL:
                return (new SolAddressValidator($this->address))->validate();
            default:
                return (new EthereumAddressValidator($this->address))->validate();
        }
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
