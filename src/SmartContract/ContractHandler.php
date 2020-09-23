<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\Token\Token;
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
    private const UPDATE_MIN_DESTINATION = 'update_mint_destination';
    private const DEPOSIT_CREDENTIAL = 'get_deposit_credential';
    private const TRANSFER = 'transfer';
    private const TRANSACTIONS = 'get_transactions';
    private const PING = 'ping';

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
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to deploy token '{$token->getName()}'");

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

    public function getDepositCredentials(User $user): string
    {
        $response = $this->rpc->send(
            self::DEPOSIT_CREDENTIAL,
            [
                'userId' => $user->getId(),
            ]
        );

        return $response->hasError() || !isset($response->getResult()['address'])
            ? 'Address unavailable.'
            : $response->getResult()['address'];
    }

    public function withdraw(User $user, Money $balance, string $address, Token $token): void
    {
        if (Token::DEPLOYED !== $token->getDeploymentStatus()) {
            $this->logger->error(
                "Failed to Update mintDestination for '{$token->getName()}' because it is not deployed"
            );

            throw new Exception('Token not deployed yet');
        }

        $response = $this->rpc->send(
            self::TRANSFER,
            [
                'userId' => $user->getId(),
                'tokenName' => $token->getName(),
                'to' => $address,
                'value' => $balance->getAmount(),
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

    private function parseTransactions(WalletInterface $wallet, array $transactions): array
    {
        $crypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);
        $depositFee = $this->moneyWrapper->format(
            $wallet->getDepositInfo($crypto ?? Token::getFromSymbol(Token::WEB_SYMBOL))->getFee()
        );
        $withdrawFee = $crypto
            ? $this->moneyWrapper->format($crypto->getFee())
            : '0';

        return array_map(function (array $transaction) use ($withdrawFee, $depositFee) {
            return new Transaction(
                (new \DateTime())->setTimestamp($transaction['timestamp']),
                (string)$transaction['hash'],
                $transaction['from'],
                $transaction['to'],
                new Money(
                    $transaction['amount'],
                    new Currency(MoneyWrapper::TOK_SYMBOL)
                ),
                $this->moneyWrapper->parse(
                    'withdraw' == $transaction['type']
                            ? $withdrawFee
                            : $depositFee,
                    MoneyWrapper::TOK_SYMBOL
                ),
                $this->tokenManager->findByName($transaction['token']),
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
