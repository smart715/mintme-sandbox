<?php declare(strict_types = 1);

namespace App\Withdraw\Communicator;

use App\Entity\Crypto;
use App\Entity\User;
use App\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use Money\Money;

interface CommunicatorInterface
{
    public function sendWithdrawRequest(User $user, Money $balance, string $address, Crypto $crypto): void;
    public function sendRetryMessage(WithdrawCallbackMessage $message): void;
}
