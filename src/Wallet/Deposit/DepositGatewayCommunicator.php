<?php declare(strict_types = 1);

namespace App\Wallet\Deposit;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\Utils\AssetType;
use App\Wallet\Deposit\Model\DepositCallbackMessage;
use App\Wallet\Deposit\Model\DepositCredentials;
use App\Wallet\Deposit\Model\ValidDeposit;
use App\Wallet\Model\BlockchainTransaction;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Money;

class DepositGatewayCommunicator implements DepositGatewayCommunicatorInterface
{
    private JsonRpcInterface $jsonRpc;

    private CryptoManagerInterface $cryptoManager;

    private MoneyWrapperInterface $moneyWrapper;

    private LimitHistoryConfig $limitHistoryConfig;

    private WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager;

    private const GET_DEPOSIT_CREDENTIALS_METHOD = "get_deposit_credentials";
    private const GET_DEPOSIT_INFO_METHOD = "get_deposit_info";

    public const GET_TRANSACTIONS_METHOD = "get_transactions";

    public const GET_BLOCKCHAIN_TRANSACTION = 'get_blockchain_transaction';
    public const CONFIRM_DEPOSIT = 'confirm_deposit';
    public const VALIDATE_DEPOSIT = 'validate_deposit';
    public const UNKNOWN_OR_DISABLED_CRYPTO_ERROR = -32098;

    public function __construct(
        JsonRpcInterface $jsonRpc,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper,
        LimitHistoryConfig $limitHistoryConfig,
        WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->cryptoManager = $cryptoManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->limitHistoryConfig = $limitHistoryConfig;
        $this->wrappedCryptoTokenManager = $wrappedCryptoTokenManager;
    }

    /** {@inheritdoc} */
    public function getDepositCredentials(int $userId, array $cryptos): DepositCredentials
    {
        $credentials = [];

        /** @var Crypto $crypto */
        foreach ($cryptos as $crypto) {
            $response = $this->jsonRpc->send(
                self::GET_DEPOSIT_CREDENTIALS_METHOD,
                [
                    'user_id' => $userId,
                    'currency' => $crypto->getSymbol(),
                ]
            );

            if ($response->hasError()) {
                $error = $response->getError();

                if (self::UNKNOWN_OR_DISABLED_CRYPTO_ERROR === (int)$error['code']) {
                    continue;
                }

                throw new FetchException($error['message'], $error['code']);
            }

            $credentials[$crypto->getSymbol()] = $response->getResult();
        }

        return new DepositCredentials($credentials);
    }

    /** {@inheritdoc} */
    public function getHistory(User $user, int $offset, int $limit): array
    {
        return $this->getTransactions($user, $offset, $limit);
    }

    /** {@inheritdoc} */
    public function getTransactions(User $user, int $offset, int $limit): array
    {
        $response = $this->jsonRpc->send(
            self::GET_TRANSACTIONS_METHOD,
            [
                'user_id' => $user->getId(),
                'userId' => $user->getId(),
                'asset' => AssetType::CRYPTO,
                'offset' => $offset,
                'limit' => $limit,
                'fromTimestamp' => $this->limitHistoryConfig->getFromDate()->getTimestamp(),
            ]
        );

        if ($response->getError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        return $this->parseTransactions($response->getResult());
    }

    public function validateDeposit(DepositCallbackMessage $clbResult): ValidDeposit
    {
        $response = $this->jsonRpc->send(self::VALIDATE_DEPOSIT, $clbResult->toArray());

        if ($response->getError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        return ValidDeposit::parse($clbResult, $response->getResult());
    }

    public function confirmDeposit(int $userId, array $hashes, string $asset, string $cryptoNetwork): void
    {
        $response = $this->jsonRpc->send(
            self::CONFIRM_DEPOSIT,
            [
                'userId' => $userId,
                'hashes' => $hashes,
                'asset' => $asset,
                'cryptoNetwork' => $cryptoNetwork,
            ]
        );

        if ($response->getError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }
    }

    public function getBlockchainTransaction(string $hash, string $asset, string $cryptoNetwork): BlockchainTransaction
    {
        $response = $this->jsonRpc->send(
            self::GET_BLOCKCHAIN_TRANSACTION,
            [
                'hash' => $hash,
                'asset' => $asset,
                'cryptoNetwork' => $cryptoNetwork,
            ]
        );

        if ($response->getError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        return BlockchainTransaction::parse($response->getResult());
    }

    public function getDepositInfo(TradableInterface $crypto, ?User $user = null): ?DepositInfo
    {
        $symbol = $crypto->getMoneySymbol();
        $response = $this->jsonRpc->send(self::GET_DEPOSIT_INFO_METHOD, [
            'currency' => $symbol,
            'userId' => $user ? $user->getId() : null,
        ]);

        if ($response->hasError()) {
            $error = $response->getError();

            if (self::UNKNOWN_OR_DISABLED_CRYPTO_ERROR === (int)$error['code']) {
                return null;
            }

            throw new FetchException((string)json_encode($error));
        }

        $result = $response->getResult();

        $minDeposit = $result['minDeposit']
            ? $this->moneyWrapper->parse((string)$result['minDeposit'], $symbol)
            : null;

        $roundedMinDeposit = $minDeposit
            ? (string)BigDecimal::of($this->moneyWrapper->format($minDeposit))
                ->multipliedBy('1')
                ->toScale($crypto->getShowSubunit(), RoundingMode::HALF_UP)
            : null;

        return new DepositInfo(
            $this->moneyWrapper->parse($result['fee'], $symbol),
            $roundedMinDeposit
                ? $this->moneyWrapper->parse($roundedMinDeposit, $crypto->getSymbol())
                : null
        );
    }

    private function parseTransactions(array $transactions): array
    {
        return array_map(function (array $transaction) {
            /** @var Crypto $crypto */
            $crypto = $this->cryptoManager->findBySymbol(
                strtoupper($transaction['crypto'])
            );

            $nativeBlockchainCrypto = $this->wrappedCryptoTokenManager->findNativeBlockchainCrypto($crypto);

            $crypto = $nativeBlockchainCrypto
                ? $nativeBlockchainCrypto->getCrypto()
                : $crypto;

            $cryptoSymbol = $crypto->getSymbol();

            $amount = $this->moneyWrapper->parse($transaction['amount'], $cryptoSymbol);
            $fee = $this->moneyWrapper->parse((string)($transaction['fee'] ?? 0), $cryptoSymbol);

            return new Transaction(
                (new \DateTime())->setTimestamp($transaction['timestamp']),
                $transaction['hash'],
                $transaction['from'],
                $transaction['to'],
                $amount,
                $fee,
                $crypto,
                Status::fromString(
                    $transaction['status']
                ),
                Type::fromString(Type::DEPOSIT),
                false,
                $crypto
            );
        }, $transactions);
    }
}
