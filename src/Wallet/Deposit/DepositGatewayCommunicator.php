<?php declare(strict_types = 1);

namespace App\Wallet\Deposit;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Deposit\Model\DepositCredentials;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;

class DepositGatewayCommunicator implements DepositGatewayCommunicatorInterface
{
    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    private const GET_DEPOSIT_CREDENTIALS_METHOD = "get_deposit_credentials";
    private const GET_DEPOSIT_INFO_METHOD = "get_deposit_info";

    public const GET_TRANSACTIONS_METHOD = "get_transactions";

    public function __construct(
        JsonRpcInterface $jsonRpc,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->cryptoManager = $cryptoManager;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getDepositCredentials(int $userId, array $predefinedTokens): DepositCredentials
    {
        $credentials = [];

        foreach ($predefinedTokens as $token) {
            $response = $this->jsonRpc->send(
                self::GET_DEPOSIT_CREDENTIALS_METHOD,
                [
                    'user_id' => $userId,
                    'currency' => $token->getName(),
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

        if ($response->getError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        return $this->parseTransactions($response->getResult(), $user);
    }

    public function getDepositInfo(string $crypto): DepositInfo
    {
        $response = $this->jsonRpc->send(self::GET_DEPOSIT_INFO_METHOD, ['currency' => $crypto]);

        if ($response->getError()) {
            throw new FetchException((string)json_encode($response->getError()));
        }

        $result = $response->getResult();

        return new DepositInfo(
            new Money($result['fee'], new Currency($crypto)),
            $result['minDeposit']
                ? new Money($result['minDeposit'], new Currency($crypto))
                : null
        );
    }

    private function parseTransactions(array $transactions, User $user): array
    {
        return array_map(function (array $transaction) use ($user) {
            return new Transaction(
                (new \DateTime())->setTimestamp($transaction['timestamp']),
                $transaction['hash'],
                $transaction['from'],
                !$user->isBlocked() ? $transaction['to'] : '',
                new Money(
                    $this->moneyWrapper->convertToDecimalIfNotation($transaction['amount'], $transaction['crypto']),
                    new Currency($transaction['crypto'])
                ),
                $this->moneyWrapper->parse($transaction['fee'] ?? '0', $transaction['crypto']),
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
