<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Entity\Token\Token;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

/** @codeCoverageIgnore */
class DonationConfig
{
    /** @var array<int|float> */
    private $donationParams;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(array $donationParams, MoneyWrapperInterface $moneyWrapper)
    {
        $this->donationParams = $donationParams;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getFee(): string
    {
        $fee = $this->donationParams['fee'] ?? 0;

        return (string)($fee / 100);
    }

    public function getMinBtcAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->donationParams['minBtcAmount'] ?? 0),
            Token::BTC_SYMBOL
        );
    }

    public function getMinMintmeAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->donationParams['minMintmeAmount'] ?? 0),
            Token::WEB_SYMBOL
        );
    }

    public function getMinTokensAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->donationParams['minTokensAmount'] ?? 0),
            Token::WEB_SYMBOL
        );
    }

    public function getDonationParams(): array
    {
        return $this->donationParams;
    }
}
