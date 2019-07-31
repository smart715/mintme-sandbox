<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\SmartContract\Config\Config;
use App\SmartContract\Model\TokenDeployResult;
use App\Wallet\Deposit\Model\DepositCredentials;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
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

    /** @var JsonRpcInterface */
    private $rpc;

    /** @var Config */
    private $config;

    /** @var LoggerInterface */
    private $logger;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    public function __construct(
        JsonRpcInterface $rpc,
        Config $config,
        LoggerInterface $logger,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->rpc = $rpc;
        $this->config = $config;
        $this->logger = $logger;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoManager = $cryptoManager;
    }

    public function deploy(Token $token): TokenDeployResult
    {
        if (!$token->getLockIn()) {
            $this->logger->error("Failed to deploy token '{$token->getName()}' because It has not a release period");

            throw new Exception('Token dose not has release period');
        }

        $response = $this->rpc->send(
            self::DEPLOY,
            [
                'name' => $token->getName(),
                'decimals' =>
                    $this->moneyWrapper->getRepository()->subunitFor(new Currency(MoneyWrapper::TOK_SYMBOL)),
                'mintDestination' => $this->config->getMintmeAddress(),
                'releasedAtCreation' =>
                    $this->moneyWrapper
                        ->parse($this->config->getTokenQuantity(), MoneyWrapper::TOK_SYMBOL)
                        ->getAmount(),
                'releasePeriod' => $token->getLockIn()->getReleasePeriod(),
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to deploy token '{$token->getName()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }

        $result = $response->getResult();

        if (!isset($result['address'])) {
            $this->logger->error("Failed to deploy token '{$token->getName()}'");

            throw new Exception('get error response');
        }

        return new TokenDeployResult($result['address']);
    }

    public function updateMinDestination(Token $token, string $address, bool $lock): void
    {
        if ($token->isMinDestinationLocked()) {
            $this->logger->error("Failed to Update minDestination for '{$token->getName()}' because It is locked");

            throw new Exception('Token dose not has release period');
        }

        $response = $this->rpc->send(
            self::UPDATE_MIN_DESTINATION,
            [
                'tokenContract' => $token->getAddress(),
                'mintDestination' => $address,
                'lock'=> $lock,
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to update minDestination for '{$token->getName()}'");

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
        if (!$token->isDeployed()) {
            $this->logger->error("Failed to Update minDestination for '{$token->getName()}' because It is locked");

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

    public function getTransactions(User $user, int $offset, int $limit): array
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

        return $this->parseTransactions($response->getResult());
    }

    private function parseTransactions(array $transactions): array
    {
        return array_map(function (array $transaction) {
            return new Transaction(
                (new \DateTime())->setTimestamp($transaction['timestamp']),
                $transaction['hash'],
                $transaction['from'],
                $transaction['to'],
                new Money(
                    $transaction['amount'],
                    new Currency(MoneyWrapper::TOK_SYMBOL)
                ),
                $this->moneyWrapper->parse(
                    'withdraw' == $transaction['type']
                            ? (string)$this->config->getWithdrawFee()
                            : (string)$this->config->getDepositFee(),
                    MoneyWrapper::TOK_SYMBOL
                ),
                $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL),
                Status::fromString('paid'),
                Type::fromString($transaction['type'])
            );
        }, $transactions);
    }
}
