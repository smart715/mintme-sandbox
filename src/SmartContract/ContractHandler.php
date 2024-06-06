<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Config\TokenConfig;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\SmartContract\Model\AddTokenResult;
use App\Utils\AssetType;
use App\Utils\Symbols;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Model\WithdrawInfo;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Exception;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;

class ContractHandler implements ContractHandlerInterface
{
    private const DEPLOY = 'deploy';
    private const ADD_TOKEN = 'add_token';
    private const UPDATE_MIN_DESTINATION = 'update_mint_destination';
    private const CONTRACT_METHOD_FEE = 'contract_method_fee';
    private const DEPOSIT_CREDENTIAL = 'get_deposit_credential';
    private const TRANSFER = 'transfer';
    private const GET_TRANSACTIONS = 'get_transactions';
    private const GET_PENDING_WITHDRAWALS = 'get_pending_withdrawals';
    private const GET_DEPOSIT_INFO = "get_deposit_info";
    private const PING = 'ping';
    private const GET_DECIMALS_CONTRACT = 'get_decimals_contract';
    private const UPDATE_TOKEN_STATUS = 'update_token_status';
    private const GET_WITHDRAW_INFO = 'get_withdraw_info';

    private JsonRpcInterface $rpc;
    private LoggerInterface $logger;
    private MoneyWrapperInterface $moneyWrapper;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private TokenConfig $tokenConfig;
    private LimitHistoryConfig $limitHistoryConfig;
    private WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager;

    public function __construct(
        JsonRpcInterface $rpc,
        LoggerInterface $logger,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        TokenConfig $tokenConfig,
        LimitHistoryConfig $limitHistoryConfig,
        WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager
    ) {
        $this->rpc = $rpc;
        $this->logger = $logger;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->tokenConfig = $tokenConfig;
        $this->limitHistoryConfig = $limitHistoryConfig;
        $this->wrappedCryptoTokenManager = $wrappedCryptoTokenManager;
    }

    public function deploy(TokenDeploy $deploy, bool $isMainDeploy): void
    {
        $token = $deploy->getToken();
        $crypto = $deploy->getCrypto();

        if (!$token->getLockIn()) {
            $this->logger->error(
                "Failed to deploy token '{$token->getSymbol()}/{$crypto->getSymbol()}' " .
                "because it does not have a release period"
            );

            throw new Exception('Token does not have a release period');
        }

        $releasedAtCreation = $isMainDeploy
            ? $token->getLockIn()->getReleasedAmount()
            : $this->tokenConfig->getTokenQuantity();

        $releasePeriod = $isMainDeploy
            ? $token->getLockIn()->getReleasePeriod()
            : 0;

        $response = $this->rpc->send(
            self::DEPLOY,
            [
                'name' => $token->getSymbol(),
                'crypto' => $crypto->getSymbol(),
                'decimals' => $this->moneyWrapper->getRepository()->subunitFor(new Currency(Symbols::TOK)),
                'releasedAtCreation' => $this->moneyWrapper->format($releasedAtCreation),
                'releasePeriod' => $releasePeriod,
                'userId' => $token->getProfile()->getUser()->getId(),
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to deploy token '{$token->getSymbol()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }
    }

    /**
     * For Token entities or Cryptos with WrappedCryptoToken relation
     */
    public function addToken(
        TradableInterface $tradable,
        Crypto $cryptoNetwork,
        string $address,
        ?string $minDeposit,
        bool $isCrypto = false,
        bool $isPausable = false
    ): AddTokenResult {
        $response = $this->rpc->send(
            self::ADD_TOKEN,
            [
                'name' => $tradable->getSymbol(),
                'address' => $address,
                'crypto' => $cryptoNetwork->getSymbol(),
                'minDeposit' => $minDeposit,
                'isCrypto' => $isCrypto,
                'isPausable' => $isPausable,
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to add tradable '{$tradable->getSymbol()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }

        return AddTokenResult::parse($response->getResult());
    }

    public function getContractMethodFee(string $cryptoSymbol): Money
    {
        $response = $this->rpc->send(
            self::CONTRACT_METHOD_FEE,
            [
                'crypto' => $cryptoSymbol,
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to get contract method fee for '{$cryptoSymbol}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }

        return $this->moneyWrapper->parse($response->getResult(), $cryptoSymbol);
    }

    public function updateMintDestination(Token $token, string $address): void
    {
        if (Token::DEPLOYED !== $token->getDeploymentStatus()) {
            $this->logger->error(
                "Failed to Update mintDestination for '{$token->getSymbol()}' because it is not deployed"
            );

            throw new Exception('Token not deployed yet');
        }

        $response = $this->rpc->send(
            self::UPDATE_MIN_DESTINATION,
            [
                'name' => $token->getSymbol(),
                'crypto' => $token->getCryptoSymbol(),
                'contractAddress' => $token->getMainDeploy()->getAddress(),
                'mintDestination' => $address,
                'oldMintDestination' => $token->getMintDestination(),
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to update mintDestination for '{$token->getSymbol()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }
    }

    public function getDepositCredentials(User $user): array
    {
        $response = $this->rpc->send(
            self::DEPOSIT_CREDENTIAL,
            [
                'userId' => $user->getId(),
            ]
        );

        if ($response->hasError()) {
            throw new \Exception((string)json_encode($response->getError()));
        }

        return $response->getResult();
    }

    /**
     * @param Token|Crypto $tradable
     */
    public function withdraw(
        User $user,
        Money $balance,
        string $address,
        TradableInterface $tradable,
        Crypto $cryptoNetwork,
        Money $fee
    ): void {
        $isValidNetwork = $tradable instanceof Token
           ? (bool)$tradable->getDeployByCrypto($cryptoNetwork)
           : $tradable->canBeWithdrawnTo($cryptoNetwork);

        if (!$isValidNetwork) {
            $this->logger->error(
                "Failed to withdraw '{$tradable->getSymbol()}' token on '{$cryptoNetwork->getSymbol()}' blockchain"
            );

            throw new Exception('Token not deployed to network');
        }

        $response = $this->rpc->send(
            self::TRANSFER,
            [
                'userId' => $user->getId(),
                'tokenName' => $tradable->getSymbol(),
                'to' => $address,
                'value' => $this->moneyWrapper->format($balance),
                'crypto' => $cryptoNetwork->getSymbol(),
                'tokenFee' => $this->moneyWrapper->format($fee),
                'tokenFeeCurrency' => $fee->getCurrency()->getCode(),
            ]
        );

        if ($response->hasError()) {
            $this->logger->error(
                "Failed to withdraw for '{$tradable->getSymbol()}' on network '{$cryptoNetwork->getSymbol()}'"
            );

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }
    }

    public function getAllRawTransactions(User $user, int $offset, int $limit): array
    {
        $response = $this->rpc->send(
            self::GET_TRANSACTIONS,
            [
                'userId' => $user->getId(),
                'asset' => AssetType::ALL,
                'offset' => $offset,
                'limit' => $limit,
                'fromTimestamp' => $this->limitHistoryConfig->getFromDate()->getTimestamp(),
            ]
        );

        if ($response->hasError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        return $response->getResult();
    }

    public function getTransactions(User $user, int $offset, int $limit): array
    {
        $response = $this->rpc->send(
            self::GET_TRANSACTIONS,
            [
                'userId' => $user->getId(),
                'asset' => AssetType::TOKEN,
                'offset' => $offset,
                'limit' => $limit,
                'fromTimestamp' => $this->limitHistoryConfig->getFromDate()->getTimestamp(),
            ]
        );

        if ($response->hasError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        return $this->parseTransactions($response->getResult(), AssetType::TOKEN);
    }

    public function getPendingWithdrawals(User $user, string $asset): array
    {
        $response = $this->rpc->send(
            self::GET_PENDING_WITHDRAWALS,
            [
                'userId' => $user->getId(),
                'asset' => $asset,
            ]
        );

        if ($response->hasError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        return $this->parseTransactions($response->getResult());
    }

    private function getFee(array $transaction): Money
    {
        // new gateway support
        $tokenFee = $transaction['tokenFee'] ?? $transaction['fee'];
        $tokenFeeCurrency = $transaction['tokenFeeCurrency'] ?? $transaction['feeCurrency'];

        return $this->moneyWrapper->parse(
            (string)($tokenFee ?? 0),
            $tokenFeeCurrency ?? Symbols::TOK
        );
    }

    private function parseTransactions(array $transactions, string $asset = AssetType::ALL): array
    {
        $parsed = [];
        $indexedCryptos = $this->cryptoManager->findAllIndexed('symbol');

        foreach ($transactions as $transaction) {
            // support for new gateway
            if (is_array($transaction['token'])) {
                $transaction['token'] = $transaction['token']['name'];
            }

            $tokenName = $transaction['token'];

            /** @var Crypto|null $cryptoToken */
            $cryptoToken = $indexedCryptos[$tokenName] ?? null;

            // skip transaction for deleted token
            if (AssetType::TOKEN === $asset && !$cryptoToken && !$tokenName) {
                continue;
            }

            $tradable = $cryptoToken ?? $this->tokenManager->findByName($tokenName);

            if (!$tradable) {
                $this->logger->info("[contract-handler] tradable name not exist ($tokenName)");
            }

            $amount = $this->moneyWrapper->parse(
                $transaction['amount'],
                $cryptoToken
                    ? $cryptoToken->getSymbol()
                    : Symbols::TOK
            );

            $parsed[] = new Transaction(
                (new \DateTime())->setTimestamp($transaction['timestamp']),
                (string)$transaction['hash'],
                $transaction['from'],
                $transaction['to'],
                $amount,
                $this->getFee($transaction),
                $tradable,
                Status::fromString($transaction['status']),
                Type::fromString($transaction['type']),
                false,
                $this->cryptoManager->findBySymbol($transaction['crypto'])
            );
        }

        return $parsed;
    }

    public function ping(): bool
    {
        $response = $this->rpc->send(self::PING, []);

        return 'pong' === $response->getResult();
    }

    public function getDepositInfo(TradableInterface $tradable, Crypto $cryptoNetwork): DepositInfo
    {
        $response = $this->rpc->send(
            self::GET_DEPOSIT_INFO,
            [
                'tokenName' => $tradable->getSymbol(),
                'crypto' => $cryptoNetwork->getSymbol(),
            ]
        );

        if ($response->hasError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        $result = $response->getResult();
        $currency = $tradable->getMoneySymbol();

        $minDeposit = $this->moneyWrapper->parse($result['minDeposit'], $currency);

        $minDeposit = (string)BigDecimal::of($minDeposit->getAmount())
            ->multipliedBy('1')
            ->toScale($tradable->getShowSubunit(), RoundingMode::HALF_UP);

        return new DepositInfo(
            $this->moneyWrapper->parse($result['fee'], $currency),
            new Money($minDeposit, new Currency($currency))
        );
    }

    public function getDecimalsContract(string $tokenAddress, string $blockchain): int
    {
        $response = $this->rpc->send(
            self::GET_DECIMALS_CONTRACT,
            [
                'address' => $tokenAddress,
                'blockchain' => $blockchain,
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to get decimals contract'{$tokenAddress}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }

        return (int)$response->getResult();
    }

    public function updateTokenStatus(
        TradableInterface $tradable,
        ?Crypto $cryptoBlockchain,
        bool $status,
        bool $runDelayedTransactions
    ): void {
        $name = $tradable->getSymbol();
        $blockchainSymbol = $cryptoBlockchain
            ? $cryptoBlockchain->getSymbol()
            : null;

        $response = $this->rpc->send(
            self::UPDATE_TOKEN_STATUS,
            [
                'name' => $name,
                'blockchain' => $blockchainSymbol,
                'enabled' => $status,
                'runDelayedTransactions' => $runDelayedTransactions,
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to update ${name}/$${blockchainSymbol} token status");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }
    }

    /** @throws FetchException */
    public function getWithdrawInfo(Crypto $cryptoNetwork, TradableInterface $tradable): WithdrawInfo
    {
        $isNativeToken = $cryptoNetwork->getSymbol() === $tradable->getSymbol();

        $wrappedCryptoToken = $tradable instanceof Crypto
            ? $this->wrappedCryptoTokenManager->findByCryptoAndDeploy($tradable, $cryptoNetwork)
            : null;
        $isNativeCryptoOnAnotherBlockchain = $wrappedCryptoToken && $wrappedCryptoToken->isNative();

        $response = $isNativeToken || $isNativeCryptoOnAnotherBlockchain
            ? $this->rpc->send(
                self::GET_WITHDRAW_INFO,
                [
                    'crypto' => $cryptoNetwork->getSymbol(),
                ]
            )
            : $this->rpc->send(
                self::GET_WITHDRAW_INFO,
                [
                    'tokenName' => $tradable->getSymbol(),
                    'crypto' => $cryptoNetwork->getSymbol(),
                ]
            );

        if ($response->hasError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        $result = $response->getResult();

        $minFee = (string)BigDecimal::of($result['minFee'])
            ->multipliedBy('1')
            ->toScale($cryptoNetwork->getShowSubunit(), RoundingMode::HALF_UP);

        return new WithdrawInfo(
            $this->moneyWrapper->parse($minFee, $cryptoNetwork->getMoneySymbol()),
            $result['isPaused'] ?? null
        );
    }
}
