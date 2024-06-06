<?php declare(strict_types = 1);

namespace App\Events;

use App\Activity\ActivityTypes;
use App\Entity\TradableInterface;
use App\Entity\User;

/** @codeCoverageIgnore */
class DepositCompletedEvent extends TransactionCompletedEvent
{
    public const NAME = "deposit.completed";
    public const TYPE = "deposit";

    public function __construct(
        TradableInterface $tradable,
        User $user,
        string $amount,
        string $cryptoNetworkName,
        string $address
    ) {
        parent::__construct($tradable, $user, $amount, $address, $cryptoNetworkName, ActivityTypes::DEPOSITED);
    }
}
