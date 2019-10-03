<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use Symfony\Component\Serializer\Annotation\Groups;

class Market
{
    /** @var TradebleInterface */
    private $base;

    /** @var TradebleInterface */
    private $quote;

    public function __construct(TradebleInterface $base, TradebleInterface $quote)
    {
        $this->base = $base;
        $this->quote = $quote;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getBase(): TradebleInterface
    {
        return $this->base;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getQuote(): TradebleInterface
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
