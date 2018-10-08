<?php

namespace App\Withdraw\Communicator;

use App\Entity\Crypto;
use App\Entity\User;
use App\Withdraw\Communicator\Model\WithdrawCallbackMessage;

interface CommunicatorInterface
{
    public function sendWithdrawRequest(User $user, string $balance, string $address, Crypto $crypto): void;
    public function sendRetryMessage(WithdrawCallbackMessage $message): void;
}
