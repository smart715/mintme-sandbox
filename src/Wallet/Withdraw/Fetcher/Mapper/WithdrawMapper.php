<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Fetcher\Mapper;

use App\Entity\Crypto;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\Withdraw\Fetcher\Storage\StorageAdapterInterface;
use Money\Money;

class WithdrawMapper implements MapperInterface
{
    /** @var StorageAdapterInterface */
    private $storage;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        StorageAdapterInterface $storage,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->storage = $storage;
        $this->cryptoManager = $cryptoManager;
        $this->moneyWrapper = $moneyWrapper;
    }

    /** {@inheritdoc} */
    public function getHistory(User $user, int $offset = 0, int $limit = 50): array
    {
        return array_map(function (array $transaction) {
            return new Transaction(
                (new \DateTime())->setTimestamp($transaction['createdDate']),
                $transaction['transactionHash'],
                null,
                $transaction['walletAddress'],
                $this->moneyWrapper->parse((string)$transaction['amount'], $transaction['crypto']),
                $this->moneyWrapper->parse((string)$transaction['fee'], $transaction['crypto']),
                $this->cryptoManager->findBySymbol(
                    strtoupper($transaction['crypto'])
                ),
                Status::fromString(
                    $transaction['status']
                ),
                Type::fromString(Type::WITHDRAW)
            );
        }, $this->storage->requestHistory($user->getId(), $offset, $limit));
    }

    public function getBalance(Crypto $crypto): Money
    {
        return $this->moneyWrapper->parse(
            $this->storage->requestBalance($crypto->getSymbol()),
            $crypto->getSymbol()
        );
    }
}
