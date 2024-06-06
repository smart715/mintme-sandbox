<?php declare(strict_types = 1);

namespace App\Events;

use App\Activity\ActivityTypes;
use App\Entity\TradableInterface;
use App\Entity\User;

/** @codeCoverageIgnore */
class WithdrawCompletedEvent extends TransactionCompletedEvent
{
    public const NAME = "withdraw.completed";
    public const TYPE = "withdraw";

    public function __construct(
        TradableInterface $tradable,
        User $user,
        string $amount,
        string $address,
        string $cryptoNetworkName
    ) {
        parent::__construct($tradable, $user, $amount, $address, $cryptoNetworkName, ActivityTypes::WITHDRAWN);
    }
}
