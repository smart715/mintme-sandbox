<?php declare(strict_types = 1);

namespace App\Wallet\Deposit;

use App\Communications\JsonRpcInterface;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Deposit\Model\DepositCredentials;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use Money\Currency;
use Money\Money;

class DepositGatewayCommunicator implements DepositGatewayCommunicatorInterface
{
    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    private const GET_DEPOSIT_CREDENTIALS_METHOD = "get_deposit_credentials";

    public const GET_TRANSACTIONS_METHOD = "get_transactions";

    public function __construct(
        JsonRpcInterface $jsonRpc,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->cryptoManager = $cryptoManager;
    }

    public function getDepositCredentials(int $userId, array $predefinedTokens): DepositCredentials
    {
        $credentials = [];

        foreach ($predefinedTokens as $token) {
            $response = $this->jsonRpc->send(
                self::GET_DEPOSIT_CREDENTIALS_METHOD,
                [
                    'user_id' => $userId,
                    "currency" => $token->getName(),
                ]
            );
            $credentials[$token->getName()] = $response->hasError() ?
                "Address unavailable." :
                $response->getResult();
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
                "offset" => $offset,
                "limit" => $limit,
            ]
        );

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
                new Money($transaction['amount'], new Currency($transaction['crypto'])),
                new Money($transaction['fee'] ?? 0, new Currency($transaction['crypto'])),
                $this->cryptoManager->findBySymbol(
                    strtoupper($transaction['crypto'])
                ),
                Status::fromString(
                    $transaction['status']
                ),
                Type::fromString(Type::DEPOSIT)
            );
        }, $transactions);
    }
}