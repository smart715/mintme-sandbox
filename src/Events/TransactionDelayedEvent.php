<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Wallet\Model\LackMainBalanceReport;
use App\Wallet\Model\Type;
use Money\Money;
use Symfony\Contracts\EventDispatcher\Event;

/** @codeCoverageIgnore */
class TransactionDelayedEvent extends Event
{
    public const NAME = 'transaction.delayed';

    private Type $type;
    private LackMainBalanceReport $report;

    public function __construct(Type $type, LackMainBalanceReport $report)
    {
        $this->type = $type;
        $this->report = $report;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getReport(): LackMainBalanceReport
    {
        return $this->report;
    }
}
