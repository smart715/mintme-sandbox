<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
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

    public function addToken(Token $token): void;

    public function updateMintDestination(Token $token, string $address): void;

    public function getDepositCredentials(User $user): array;

    public function withdraw(User $user, Money $balance, string $address, TradebleInterface $token): void;

    public function getTransactions(WalletInterface $wallet, User $user, int $offset, int $limit): array;

    public function ping(): bool;
}
