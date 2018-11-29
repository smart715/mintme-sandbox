<?php

namespace App\Tests\Withdraw\Fetcher;

use App\Entity\Crypto;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Withdraw\Fetcher\Mapper\WithdrawMapper;
use App\Withdraw\Fetcher\Storage\StorageAdapterInterface;
use App\Withdraw\Payment\Transaction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WithdrawMapperTest extends TestCase
{
    public function testGetHistory(): void
    {
        $data = [
            [
                'transactionHash' => '123456',
                'createdDate' => 6543213,
                'status' => 'paid',
                'crypto' => 'web',
                'fee' => 0.1,
                'walletAddress' => '1234567890',
                'amount' => 21,
            ],
        ];
        $storage = $this->mockStorageAdapter($data);
        $mapper = new WithdrawMapper($storage, $this->mockCryptoManager($this->mockCrypto($data[0]['crypto'])));
        $history = $mapper->getHistory($this->mockUser());
        /** @var Transaction $transaction */
        $transaction = $history[0];

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($data[0]['createdDate'], $transaction->getDate()->getTimestamp());
        $this->assertEquals($data[0]['status'], $transaction->getStatus()->getStatusCode());
        $this->assertEquals($data[0]['crypto'], $transaction->getCrypto()->getSymbol());
    }

    public function testGetBalance(): void
    {
        $data = [ 123.0 ];
        $storage = $this->mockStorageAdapter($data);
        $mapper = new WithdrawMapper($storage, $this->mockCryptoManager($this->mockCrypto()));
        $balance = $mapper->getBalance($this->mockCrypto());

        $this->assertEquals($data[0], $balance->getAmount());
    }

    /** @return MockObject|CryptoManagerInterface */
    private function mockCryptoManager(?Crypto $crypto): CryptoManagerInterface
    {
        $manager = $this->createMock(CryptoManagerInterface::class);

        $manager->method('findBySymbol')->willReturn($crypto);

        return $manager;
    }

    /** @return StorageAdapterInterface|MockObject */
    public function mockStorageAdapter(array $params): StorageAdapterInterface
    {
        $storage = $this->createMock(StorageAdapterInterface::class);

        $storage->method('requestHistory')->willReturn($params);
        $storage->method('requestBalance')->willReturn($params[0]);

        return $storage;
    }

    /** @return Crypto|MockObject */
    public function mockCrypto(string $symbol = 'web'): Crypto
    {
        $user = $this->createMock(Crypto::class);

        $user->method('getSymbol')->willReturn('web');

        return $user;
    }

    /** @return User|MockObject */
    public function mockUser(): User
    {
        $user = $this->createMock(User::class);

        $user->method('getId')->willReturn(1);

        return $user;
    }
}
