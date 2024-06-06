<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Fetcher\Mapper;

use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\Withdraw\Fetcher\Storage\StorageAdapterInterface;
use Money\Currency;
use Money\Money;

class WithdrawMapper implements MapperInterface
{
    private StorageAdapterInterface $storage;

    private CryptoManagerInterface $cryptoManager;

    private MoneyWrapperInterface $moneyWrapper;

    private LimitHistoryConfig $limitHistoryConfig;

    private WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager;

    public function __construct(
        StorageAdapterInterface $storage,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper,
        LimitHistoryConfig $limitHistoryConfig,
        WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager
    ) {
        $this->storage = $storage;
        $this->cryptoManager = $cryptoManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->limitHistoryConfig = $limitHistoryConfig;
        $this->wrappedCryptoTokenManager = $wrappedCryptoTokenManager;
    }

    /** {@inheritdoc} */
    public function getHistory(User $user, int $offset = 0, int $limit = 50): array
    {
        return array_map(function (array $transaction) {
            // new gateway support
            $timestamp = $transaction['createdDate'] ?? $transaction['timestamp'];

            $hash = $transaction['transactionHash'] ?? $transaction['hash'];
            $from = $transaction['from'] ?? null;
            $to = $transaction['walletAddress'] ?? $transaction['to'];

            $amount = (string)$transaction['amount'];
            $fee = (string)$transaction['fee'];

            /** @var Crypto $crypto */
            $crypto = $this->cryptoManager->findBySymbol(strtoupper($transaction['crypto']));

            $nativeBlockchainCrypto = $this->wrappedCryptoTokenManager->findNativeBlockchainCrypto($crypto);

            $nativeCrypto = $nativeBlockchainCrypto
                ? $nativeBlockchainCrypto->getCrypto()
                : $crypto;
            $nativeCryptoSymbol = $nativeCrypto->getSymbol();

            $amount = $this->moneyWrapper->parse($amount, $nativeCryptoSymbol);
            $fee = $this->moneyWrapper->parse($fee, $nativeCryptoSymbol);

            return new Transaction(
                (new \DateTime())->setTimestamp($timestamp),
                $hash,
                $from,
                $to,
                $amount,
                $fee,
                $nativeCrypto,
                Status::fromString($transaction['status']),
                Type::fromString(Type::WITHDRAW),
                false,
                $nativeCrypto
            );
        }, $this->storage->requestHistory(
            $user->getId(),
            $offset,
            $limit,
            $this->limitHistoryConfig->getFromDate()->getTimestamp()
        ));
    }

    public function getBalance(TradableInterface $tradable, Crypto $cryptoNetwok): Money
    {
        $balance = $this->storage->requestBalance($tradable->getSymbol(), $cryptoNetwok->getSymbol());

        return $this->moneyWrapper->parse($balance, $tradable->getMoneySymbol());
    }

    public function isContractAddress(string $address, string $crypto): bool
    {
        return '0x' !== $this->storage->requestAddressCode($address, $crypto);
    }

    public function getUserId(string $address, string $cryptoNetwork): ?int
    {
        return $this->storage->requestUserId($address, $cryptoNetwork);
    }

    public function getCryptoIncome(string $crypto, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->storage->requestCryptoIncome($crypto, $from, $to);
    }
}
