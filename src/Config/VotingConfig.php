<?php declare(strict_types = 1);

namespace App\Config;

use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

/** @codeCoverageIgnore */
class VotingConfig
{
    /** @var array<int|float> */
    private array $votingConfig;

    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(array $votingConfig, MoneyWrapperInterface $moneyWrapper)
    {
        $this->votingConfig = $votingConfig;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getProposalMinAmountNumeric(): float
    {
        return $this->votingConfig['proposal_min_amount'] ?? 100;
    }

    public function getProposalMinAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)$this->getProposalMinAmountNumeric(),
            Symbols::WEB
        );
    }

    public function getMinBalanceToVoteNumeric(): float
    {
        return $this->votingConfig['min_balance_to_vote'] ?? 0;
    }

    public function getMinBalanceToVote(): Money
    {
        return $this->moneyWrapper->parse(
            (string)$this->getMinBalanceToVoteNumeric(),
            Symbols::WEB
        );
    }

    public function getVotingConfig(): array
    {
        return $this->votingConfig;
    }
}
