<?php declare(strict_types = 1);

namespace App\Tests\Wallet\Deposit;

use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\Tests\Mocks\MockMoneyWrapper;
use App\Wallet\Deposit\DepositGatewayCommunicator;
use PHPUnit\Framework\TestCase;

class DepositGatewayCommunicatorTest extends TestCase
{

    use MockMoneyWrapper;

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
            $this->mockCryptoManager($this->mockCrypto()),
            $this->mockMoneyWrapper(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager()
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
                $transaction->getTradable()->getSymbol(),
                $transaction->getStatus()->getStatusCode(),
            ]
        );
    }

    public function testGetHistory(): void
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
            $this->mockCryptoManager($this->mockCrypto()),
            $this->mockMoneyWrapper(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager()
        );

        $transactions = $depositCommunicator->getHistory($this->mockUser(), 0, 1);

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
                $transaction->getTradable()->getSymbol(),
                $transaction->getStatus()->getStatusCode(),
            ]
        );
    }

    public function testGetDepositInfo(): void
    {
        $fakeDepositInfo = [
            'fee' => '147',
            'minDeposit' => 10,
        ];

        $depositCommunicator = new DepositGatewayCommunicator(
            $this->mockRpcInterface($fakeDepositInfo),
            $this->mockCryptoManager($this->mockCrypto()),
            $this->mockMoneyWrapper(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager()
        );

        $depositInfo = $depositCommunicator->getDepositInfo($this->mockCrypto());

        $this->assertEquals(
            [
                $depositInfo->getFee()->getAmount(),
                $depositInfo->getMinDeposit()->getAmount(),
            ],
            [
                $fakeDepositInfo['fee'],
                $fakeDepositInfo['minDeposit'],
            ]
        );
    }

    public function testGetDepositCredentials(): void
    {
        $fakeDepositCredentials = "TEST";

        $depositCommunicator = new DepositGatewayCommunicator(
            $this->mockRpcInterface($fakeDepositCredentials),
            $this->mockCryptoManager($this->mockCrypto()),
            $this->mockMoneyWrapper(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager()
        );

        $depositCredentials = $depositCommunicator->getDepositCredentials(1, [$this->mockCrypto()]);

        $this->assertEquals("TEST", $depositCredentials->getAddress('WEB'));
    }

    /** @param array|string $response */
    private function mockRpcInterface($response): JsonRpcInterface
    {
        $rpcInterfaceMock = $this->createMock(JsonRpcInterface::class);
        $rpcInterfaceMock->method('send')
            ->willReturnCallback(function (string $method, array $params) use ($response) {
                $this->assertTrue(in_array(
                    $method,
                    ['get_transactions', 'get_deposit_info', 'get_deposit_credentials']
                ));
                $this->assertIsArray($params);

                return $this->mockRpcResponse($response);
            });

        return $rpcInterfaceMock;
    }

    /** @param array|string $response */
    private function mockRpcResponse($response): JsonRpcResponse
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

    private function mockCrypto(string $symbol = 'WEB'): Crypto
    {
        $user = $this->createMock(Crypto::class);

        $user->method('getSymbol')->willReturn($symbol);
        $user->method('getMoneySymbol')->willReturn($symbol);

        return $user;
    }

    private function mockLimitHistoryConfig(): LimitHistoryConfig
    {
        return $this->createMock(LimitHistoryConfig::class);
    }

    private function mockWrappedCryptoTokenManager(): WrappedCryptoTokenManagerInterface
    {
        return $this->createMock(WrappedCryptoTokenManagerInterface::class);
    }
}
