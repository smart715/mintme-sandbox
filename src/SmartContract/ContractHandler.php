<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapper;
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
    private const PING = 'ping';
    private const WITHDRAW_TYPE = 'withdraw';
    private const CRYPTO_TOKENS = [
        'USDC' => 'USDC',
    ];

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

    /** @var array|Money[] $cryptoFees */
    private $cryptoFees = [];

    public function __construct(
        JsonRpcInterface $rpc,
        LoggerInterface $logger,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager
    ) {
        $this->rpc = $rpc;
        $this->logger = $logger;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
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
                    $this->moneyWrapper->getRepository()->subunitFor(new Currency(MoneyWrapper::TOK_SYMBOL)),
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

    public function addToken(Token $token): void
    {
        $response = $this->rpc->send(
            self::ADD_TOKEN,
            [
                'name' => $token->getName(),
                'address' => $token->getAddress(),
                'crypto' => $token->getCryptoSymbol(),
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to add token '{$token->getName()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }
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

    public function withdraw(User $user, Money $balance, string $address, TradebleInterface $token): void
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
        string $cryptoSymbol,
        string $type,
        array $indexedCryptos,
        WalletInterface $wallet
    ): Money {
        /** @var Crypto|null $crypto */
        $crypto = $indexedCryptos[$cryptoSymbol];
        $key = "{$type}_{$cryptoSymbol}";
        $isCrypto = $crypto && isset(self::CRYPTO_TOKENS[$crypto->getSymbol()]);
        $zeroAmount = $this->moneyWrapper->parse('0', MoneyWrapper::TOK_SYMBOL);

        if (!$crypto) {
            return $zeroAmount;
        }

        if (!isset($this->cryptoFees[$key])) {
            $this->cryptoFees[$key] = self::WITHDRAW_TYPE === $type
                ? $crypto->getFee()
                : ($isCrypto ? $zeroAmount : $wallet->getDepositInfo($crypto)->getFee())
            ;
        }

        return $this->cryptoFees[$key];
    }

    private function parseTransactions(WalletInterface $wallet, array $transactions): array
    {
        $indexedCryptos = $this->cryptoManager->findAllIndexed('symbol');

        return array_map(function (array $transaction) use ($indexedCryptos, $wallet) {
            $tokenName = $transaction['token'];

            /** @var Crypto|null $cryptoToken */
            $cryptoToken = $indexedCryptos[$tokenName] ?? null;

            return new Transaction(
                (new \DateTime())->setTimestamp($transaction['timestamp']),
                (string)$transaction['hash'],
                $transaction['from'],
                $transaction['to'],
                new Money(
                    $transaction['amount'],
                    new Currency($cryptoToken ? $cryptoToken->getSymbol() : MoneyWrapper::TOK_SYMBOL)
                ),
                $this->getFee(
                    isset(self::CRYPTO_TOKENS[$tokenName])
                        ? $tokenName
                        : $transaction['crypto'],
                    $transaction['type'],
                    $indexedCryptos,
                    $wallet
                ),
                $cryptoToken ?? $this->tokenManager->findByName($tokenName),
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
}
