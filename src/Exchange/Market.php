<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use Symfony\Component\Serializer\Annotation\Groups;

class Market
{
    private TradableInterface $base;
    private TradableInterface $quote;

    public function __construct(TradableInterface $base, TradableInterface $quote)
    {
        $this->base = $base;
        $this->quote = $quote;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getBase(): TradableInterface
    {
        return $this->base;
    }

    public function setBase(TradableInterface $base): void
    {
        $this->base = $base;
    }

    public function setQuote(TradableInterface $quote): void
    {
        $this->quote = $quote;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getQuote(): TradableInterface
    {
        return $this->quote;
    }

    public function isTokenMarket(): bool
    {
        return $this->base instanceof Token || $this->quote instanceof Token;
    }

    public function __toString(): string
    {
        return $this->base->getSymbol() . '/' . $this->quote->getSymbol();
    }
}
