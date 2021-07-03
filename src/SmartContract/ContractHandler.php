<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Config\TokenConfig;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Exception;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;

class ContractHandler implements ContractHandlerInterface
{
    private const DEPLOY = 'deploy';
    private const ADD_TOKEN = 'add_token';
    private const UPDATE_MIN_DESTINATION = 'update_mint_destination';
    private const DEPOSIT_CREDENTIAL = 'get_deposit_credential';
    private const TRANSFER = 'transfer';
    private const TRANSACTIONS = 'get_transactions';
    private const GET_DEPOSIT_INFO = "get_deposit_info";
    private const PING = 'ping';
    private const DEPOSIT_TYPE = 'deposit';
    private const GET_DECIMALS_CONTRACT = 'get_decimals_contract';
    private const GET_TX_HASH = 'get_tx_hash';

    /** @var JsonRpcInterface */
    private $rpc;

    /** @var LoggerInterface */
    private $logger;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    private TokenConfig $tokenConfig;

    public function __construct(
        JsonRpcInterface $rpc,
        LoggerInterface $logger,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        TokenConfig $tokenConfig
    ) {
        $this->rpc = $rpc;
        $this->logger = $logger;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->tokenConfig = $tokenConfig;
    }

    public function deploy(Token $token): void
    {
        if (!$token->getLockIn()) {
            $this->logger->error("Failed to deploy token '{$token->getName()}' because it does not have a release period");

            throw new Exception('Token does not have a release period');
        }

        $response = $this->rpc->send(
            self::DEPLOY,
            [
                'name' => $token->getName(),
                'decimals' =>
                    $this->moneyWrapper->getRepository()->subunitFor(new Currency(Symbols::TOK)),
                'releasedAtCreation' => $token->getLockIn()->getReleasedAmount()->getAmount(),
                'releasePeriod' => $token->getLockIn()->getReleasePeriod(),
                'userId' => $token->getProfile()->getUser()->getId(),
                'crypto' => $token->getCryptoSymbol(),
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to deploy token '{$token->getName()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }
    }

    public function getTxHash(string $tokenName): string
    {
        $response = $this->rpc->send(
            self::GET_TX_HASH,
            [
                'name' => $tokenName,
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to get tx_hash for token '{$tokenName}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }

        return (string)$response->getResult();
    }

    public function addToken(Token $token, ?string $minDeposit): Token
    {
        $response = $this->rpc->send(
            self::ADD_TOKEN,
            [
                'name' => $token->getName(),
                'address' => $token->getAddress(),
                'crypto' => $token->getCryptoSymbol(),
                'minDeposit' => $minDeposit,
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to add token '{$token->getName()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }

        return $token->setDecimals((int)$response->getResult()['decimals']);
    }

    public function updateMintDestination(Token $token, string $address): void
    {
        if (Token::DEPLOYED !== $token->getDeploymentStatus()) {
            $this->logger->error(
                "Failed to Update mintDestination for '{$token->getName()}' because it is not deployed"
            );

            throw new Exception('Token not deployed yet');
        }

        $response = $this->rpc->send(
            self::UPDATE_MIN_DESTINATION,
            [
                'name' => $token->getName(),
                'contractAddress' => $token->getAddress(),
                'mintDestination' => $address,
                'oldMintDestination' => $token->getMintDestination(),
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to update mintDestination for '{$token->getName()}'");

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

    public function withdraw(User $user, Money $balance, string $address, TradebleInterface $token, ?Money $fee = null): void
    {
        if ($token instanceof Token && Token::DEPLOYED !== $token->getDeploymentStatus()) {
            $this->logger->error(
                "Failed to Update mintDestination for '{$token->getName()}' because it is not deployed"
            );

            throw new Exception('Token not deployed yet');
        }

        $response = $this->rpc->send(
            self::TRANSFER,
            [
                'userId' => $user->getId(),
                'tokenName' => $token instanceof Token ? $token->getName() : $token->getSymbol(),
                'to' => $address,
                'value' => $balance->getAmount(),
                'crypto' => $token instanceof Token ? $token->getCryptoSymbol() : $token->getSymbol(),
                'tokenFee' => $fee ? $fee->getAmount() : '0',
                'tokenFeeCurrency' => $fee ? $fee->getCurrency()->getCode() : 'TOK',
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to withdraw for '{$token->getName()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }
    }

    public function getTransactions(WalletInterface $wallet, User $user, int $offset, int $limit): array
    {
        $response = $this->rpc->send(
            self::TRANSACTIONS,
            [
                'userId' => $user->getId(),
                "offset" => $offset,
                "limit" => $limit,
            ]
        );

        if ($response->hasError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        return $this->parseTransactions($wallet, $response->getResult());
    }

    private function getFee(
        ?TradebleInterface $tradeble,
        string $type,
        WalletInterface $wallet,
        array $transaction = []
    ): Money {
        if (isset($transaction['tokenFee']) && (isset($transaction['tokenFeeCurrency']) || $transaction['token'])) {
            return new Money($transaction['tokenFee'], new Currency($transaction['tokenFeeCurrency'] ?:
                $transaction['token']));
        }

        if (!$tradeble) {
            return $this->moneyWrapper->parse('0', Symbols::TOK);
        }

        if (self::DEPOSIT_TYPE === $type) {
            return $wallet->getDepositInfo($tradeble)->getFee();
        }

        if ($tradeble instanceof Crypto) {
            return $tradeble->getFee();
        }

        /** @var Token $tradeble */
        return in_array($cryptoSymbol = $tradeble->getCryptoSymbol(), [Symbols::ETH, Symbols::BNB], true)
            ? $tradeble->getFee() ?? $this->tokenConfig->getWithdrawFeeByCryptoSymbol($cryptoSymbol)
            : $this->cryptoManager->findBySymbol($cryptoSymbol)->getFee();
    }

    private function parseTransactions(WalletInterface $wallet, array $transactions): array
    {
        $indexedCryptos = $this->cryptoManager->findAllIndexed('symbol');

        return array_map(function (array $transaction) use ($indexedCryptos, $wallet) {
            $tokenName = $transaction['token'];

            /** @var Crypto|null $cryptoToken */
            $cryptoToken = $indexedCryptos[$tokenName] ?? null;
            $tradeble = $cryptoToken ?? $this->tokenManager->findByName($tokenName);

            if (!$tradeble) {
                $this->logger->info("[contract-handler] traedable name not exist ($tokenName)");
            }

            return new Transaction(
                (new \DateTime())->setTimestamp($transaction['timestamp']),
                (string)$transaction['hash'],
                $transaction['from'],
                $transaction['to'],
                new Money(
                    $transaction['amount'],
                    new Currency($cryptoToken ? $cryptoToken->getSymbol() : Symbols::TOK)
                ),
                $this->getFee(
                    $tradeble,
                    $transaction['type'],
                    $wallet,
                    $transaction
                ),
                $tradeble,
                Status::fromString($transaction['status']),
                Type::fromString($transaction['type'])
            );
        }, $transactions);
    }

    public function ping(): bool
    {
        $response = $this->rpc->send(self::PING, []);

        return 'pong' === $response->getResult();
    }

    public function getDepositInfo(string $symbol): DepositInfo
    {
        $response = $this->rpc->send(
            self::GET_DEPOSIT_INFO,
            [
                'tokenName' => $symbol,
            ]
        );

        if ($response->hasError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        $result = $response->getResult();

        return new DepositInfo(
            new Money($result['fee'], new Currency(Symbols::TOK)),
            new Money($result['minDeposit'], new Currency(Symbols::TOK))
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
}
