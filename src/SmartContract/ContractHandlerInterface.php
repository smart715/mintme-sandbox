<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\SmartContract\Model\TokenDeployResult;
use App\Wallet\Deposit\Model\DepositCredentials;
use App\Wallet\Model\Address;
use Exception;
use Money\Money;

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

    public function withdraw(User $user, Money $balance, string $address, Token $token): void;

    public function getTransactions(User $user, int $offset, int $limit): array;
}
