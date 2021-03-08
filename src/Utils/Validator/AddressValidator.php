<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Utils\Symbols;

class AddressValidator implements ValidatorInterface
{
    /** @var TradebleInterface|null */
    private $tradable;

    /** @var string */
    private $address;

    /** @var string */
    private $message;

    public function __construct(TradebleInterface $tradable, string $address)
    {
        $this->tradable = $tradable;
        $this->address = $address;
    }

    public function validate(): bool
    {
        $this->message = "Invalid {$this->tradable->getSymbol()} address";

        return Symbols::BTC === $this->tradable->getSymbol()
            ? (new BTCAddressValidator($this->address))->validate()
            : (new EthereumAddressValidator($this->address))->validate();
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
