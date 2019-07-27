<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Entity\Token\Token;
use App\Entity\User;
use App\SmartContract\Model\TokenDeployResult;
use App\Wallet\Deposit\Model\DepositCredentials;
use App\Wallet\Model\Address;
use Exception;

interface ContractHandlerInterface
{
    /**
     * @throws Exception
     * @param Token $token
     * @return TokenDeployResult
     */
    public function deploy(Token $token): TokenDeployResult;

    public function updateMinDestination(Token $token, string $address, bool $lock): void;

    public function getDepositCredentials(User $user): DepositCredentials;
}
