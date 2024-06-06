<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

/** @codeCoverageIgnore */
class TokenSignupBonusConfig
{
    private array $tokenSignupBonusParams;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(array $tokenSignupBonusParams, MoneyWrapperInterface $moneyWrapper)
    {
        $this->tokenSignupBonusParams = $tokenSignupBonusParams;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getMinTokensAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->tokenSignupBonusParams['min_tokens_amount'] ?? 0),
            Symbols::TOK
        );
    }

    public function getMinTokenReward(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->tokenSignupBonusParams['min_token_reward'] ?? 0),
            Symbols::TOK
        );
    }

    public function getMinParticipantsAmount(): int
    {
        return (int)($this->tokenSignupBonusParams['min_participants_amount'] ?? 0);
    }

    public function getMaxParticipantsAmount(): int
    {
        return (int)($this->tokenSignupBonusParams['max_participants_amount'] ?? 0);
    }
}
