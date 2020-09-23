<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

/** @codeCoverageIgnore */
class AirdropConfig
{
    /** @var array<int|float> */
    private $airdropParams;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(array $airdropParams, MoneyWrapperInterface $moneyWrapper)
    {
        $this->airdropParams = $airdropParams;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getMinTokensAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->airdropParams['min_tokens_amount'] ?? 0),
            MoneyWrapper::TOK_SYMBOL
        );
    }

    public function getMinTokenReward(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->airdropParams['min_token_reward'] ?? 0),
            MoneyWrapper::TOK_SYMBOL
        );
    }

    public function getMinParticipantsAmount(): int
    {
        return (int)($this->airdropParams['min_participants_amount'] ?? 0);
    }

    public function getMaxParticipantsAmount(): int
    {
        return (int)($this->airdropParams['max_participants_amount'] ?? 0);
    }

    public function getAirdropParams(): array
    {
        return $this->airdropParams;
    }
}
