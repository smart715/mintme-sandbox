<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

class TradeInfo
{
    private array $topHolders;
    private string $volumeDonation;
    private Money $soldOnMarket;
    private string $sellSummary;
    private ?Money $tokenExchange = null; // phpcs:ignore
    private ?Token $token = null; // phpcs:ignore

    public function __construct(
        array $topHolders,
        string $volumeDonation,
        Money $soldOnMarket,
        string $sellSummary
    ) {
        $this->topHolders = $topHolders;
        $this->volumeDonation = $volumeDonation;
        $this->soldOnMarket = $soldOnMarket;
        $this->sellSummary = $sellSummary;
    }

    public function setTokenExchange(Money $tokenExchange): self
    {
        $this->tokenExchange = $tokenExchange;

        return $this;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @Groups({"API"})
     */
    public function getTopHolders(): array
    {
        return $this->topHolders;
    }

    /**
     * @Groups({"API"})
     */
    public function getVolumeDonation(): string
    {
        return $this->volumeDonation;
    }

    /**
     * @Groups({"API"})
     */
    public function getSoldOnMarket(): Money
    {
        return $this->soldOnMarket;
    }

    /**
     * @Groups({"API"})
     */
    public function getSellSummary(): string
    {
        return $this->sellSummary;
    }

    /**
     * @Groups({"API"})
     */
    public function getTokenExchange(): ?Money
    {
        return $this->tokenExchange ?? null;
    }

    /**
     * @Groups({"API"})
     */
    public function getLockIn(): ?LockIn
    {
        return $this->token
            ? $this->token->getLockIn() ?? null
            : null;
    }

    /**
     * @Groups({"API"})
     */
    public function getWithdrawn(): Money
    {
        return new Money(
            $this->token ? $this->token->getWithdrawn() : '0',
            new Currency(Token::TOK_SYMBOL)
        );
    }
}
