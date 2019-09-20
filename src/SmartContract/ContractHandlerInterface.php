<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Entity\Token\Token;
use App\Entity\User;
use App\SmartContract\Model\TokenDeployResult;
use App\Wallet\WalletInterface;
use Exception;
use Money\Money;

interface ContractHandlerInterface
{
    /**
     * @throws Exception
     * @param Token $token
     */
    public function deploy(Token $token): void;

    public function updateMinDestination(Token $token, string $address, bool $lock): void;

    public function getDepositCredentials(User $user): string;

    public function withdraw(User $user, Money $balance, string $address, Token $token): void;

    public function getTransactions(WalletInterface $wallet, User $user, int $offset, int $limit): array;
}
