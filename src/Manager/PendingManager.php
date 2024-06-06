<?php declare(strict_types = 1);

namespace App\Manager;

use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Repository\PendingTokenWithdrawRepository;
use App\Repository\PendingWithdrawRepository;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;

class PendingManager implements PendingManagerInterface
{
    private EntityManagerInterface $em;

    private PendingTokenWithdrawRepository $pendingTokenRepository;

    private PendingWithdrawRepository $pendingCryptoRepository;

    private LimitHistoryConfig $limitHistoryConfig;

    public function __construct(
        EntityManagerInterface $em,
        PendingTokenWithdrawRepository $pendingTokenRepository,
        PendingWithdrawRepository $pendingCryptoRepository,
        LimitHistoryConfig $limitHistoryConfig
    ) {
        $this->em = $em;
        $this->pendingTokenRepository = $pendingTokenRepository;
        $this->pendingCryptoRepository = $pendingCryptoRepository;
        $this->limitHistoryConfig = $limitHistoryConfig;
    }

    /** @param Crypto|Token $tradable */
    public function create(
        User $user,
        Address $address,
        Amount $amount,
        TradableInterface $tradable,
        Money $fee,
        Crypto $cryptoNetwork
    ): PendingWithdrawInterface {
        $pending = $tradable instanceof Token
            ? new PendingTokenWithdraw($user, $tradable, $cryptoNetwork, $amount, $address, $fee)
            : new PendingWithdraw($user, $tradable, $cryptoNetwork, $amount, $address, $fee);

        $this->em->persist($pending);
        $this->em->flush();

        return $pending;
    }

    public function getPendingTokenWithdraw(User $user, int $offset, int $limit): array
    {
        /** @var array $pending */
        $pending = $this->pendingTokenRepository->getPending(
            $user,
            $offset,
            $limit,
            $this->limitHistoryConfig->getFromDate()
        );

        return $this->buildPendingTransaction($pending);
    }

    public function getPendingCryptoWithdraw(User $user, int $offset, int $limit): array
    {
        /** @var array $pending */
        $pending = $this->pendingCryptoRepository->getPending(
            $user,
            $offset,
            $limit,
            $this->limitHistoryConfig->getFromDate()
        );

        return $this->buildPendingTransaction($pending);
    }

    private function buildPendingTransaction(array $transactions): array
    {
        return array_map(function ($transaction) {
            /** @var PendingWithdraw|PendingTokenWithdraw $transaction */
            $tradable = $transaction instanceof PendingTokenWithdraw
                    ? $transaction->getToken()
                    : $transaction->getCrypto();

            return new Transaction(
                $transaction->getDate(),
                null,
                null,
                $transaction->getAddress()->getAddress(),
                $transaction->getAmount()->getAmount(),
                $transaction->getFee(),
                $tradable,
                Status::fromString(Status::CONFIRMATION),
                Type::fromString(Type::WITHDRAW),
                false,
                $transaction->getCryptoNetwork()
            );
        }, $transactions);
    }
}
