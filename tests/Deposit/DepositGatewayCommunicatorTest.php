<?php

namespace App\Tests\Deposit;

use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Deposit\DepositGatewayCommunicator;
use App\Entity\Crypto;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class DepositGatewayCommunicatorTest extends TestCase
{
    public function testGetTransactions(): void
    {
        $amount = '963';

        $fakeTransactions = [
            [
                'hash' => '0x9fc76417374aa880d4449a1f7f31ec597f00b1f6f3dd2d66f4c9c6c445836d8b',
                'from' => '0xa94f5374fce5edbc8e2a8697c15331677e6ebf0b',
                'to' => '0x6295ee1b4f6dd65047762f924ecd367c17eabf8f',
                'amount' => $amount,
                'fee' => '147',
                'timestamp' => 987654,
                'crypto' => 'WEB',
                'status' => 'pending',
            ],
        ];
        $fakeTransaction = $fakeTransactions[0];

        $depositCommunicator = new DepositGatewayCommunicator(
            $this->mockRpcInterface($fakeTransactions),
            $this->mockCryptoManager($this->mockCrypto())
        );

        $transactions = $depositCommunicator->getTransactions($this->mockUser(), 0, 1);

        $this->assertEquals(1, count($transactions));

        $transaction = $transactions[0];

        $this->assertEquals(
            [
                $fakeTransaction['hash'],
                $fakeTransaction['from'],
                $fakeTransaction['to'],
                $fakeTransaction['amount'],
                $fakeTransaction['fee'],
                $fakeTransaction['timestamp'],
                $fakeTransaction['crypto'],
                $fakeTransaction['status'],
            ],
            [
                $transaction->getHash(),
                $transaction->getFromAddress(),
                $transaction->getToAddress(),
                $transaction->getAmount()->getAmount(),
                $transaction->getFee()->getAmount(),
                $transaction->getDate()->getTimestamp(),
                $transaction->getCrypto()->getSymbol(),
                $transaction->getStatus()->getStatusCode(),
            ]
        );
    }

    private function mockRpcInterface(array $response): JsonRpcInterface
    {
        $rpcInterfaceMock = $this->createMock(JsonRpcInterface::class);
        $rpcInterfaceMock
            ->method('send')
            ->with(
                $this->equalTo(DepositGatewayCommunicator::GET_TRANSACTIONS_METHOD),
                $this->isType('array')
            )
            ->willReturn($this->mockRpcResponse($response))
        ;
        return $rpcInterfaceMock;
    }

    private function mockRpcResponse(array $response): JsonRpcResponse
    {
        $rpcResponseMock = $this->createMock(JsonRpcResponse::class);
        $rpcResponseMock
            ->method('getResult')
            ->willReturn($response)
        ;
        return $rpcResponseMock;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockCryptoManager(?Crypto $crypto): CryptoManagerInterface
    {
        $manager = $this->createMock(CryptoManagerInterface::class);

        $manager->method('findBySymbol')->willReturn($crypto);

        return $manager;
    }

    public function mockCrypto(string $symbol = 'WEB'): Crypto
    {
        $user = $this->createMock(Crypto::class);

        $user->method('getSymbol')->willReturn($symbol);

        return $user;
    }
}
