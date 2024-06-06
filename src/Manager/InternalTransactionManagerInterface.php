<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Manager\Model\InternalTransferModel;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use Money\Money;

interface InternalTransactionManagerInterface
{
    public function transferFunds(
        User $user,
        User $recipient,
        TradableInterface $tradable,
        Crypto $cryptoNetwork,
        Amount $amount,
        Address $address,
        Money $fee
    ): InternalTransferModel;

    public function getLatest(
        User $user,
        int $offset,
        int $limit
    ): array;

    public function getInternalTransactionsProfits(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array;
}
