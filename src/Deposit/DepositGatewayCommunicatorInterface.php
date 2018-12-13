<?php

namespace App\Deposit;

use App\Deposit\Model\DepositCredentials;

interface DepositGatewayCommunicatorInterface
{
    public function getDepositCredentials(int $userId, array $predefinedToken): DepositCredentials;
    public function getUnavailableCredentials(array $predefinedTokens): DepositCredentials;
}
