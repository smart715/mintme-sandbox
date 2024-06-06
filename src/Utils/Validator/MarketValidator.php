<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Exchange\Market;

class MarketValidator implements ValidatorInterface
{
    private TradableInterface $base;
    private TradableInterface $quote;

    /** @var string */
    private $message = 'Invalid Market';

    public function __construct(Market $market)
    {
        $this->base = $market->getBase();
        $this->quote = $market->getQuote();
    }

    public function validate(): bool
    {
        return $this->validateBase() && $this->validateQuote();
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    private function validateBase(): bool
    {
        return $this->base instanceof Crypto
            && $this->base->getSymbol() !== $this->quote->getSymbol();
    }

    private function validateQuote(): bool
    {
        return !$this->quote instanceof Token ||
            ($this->base instanceof Crypto && $this->quote->containsExchangeCrypto($this->base));
    }
}
