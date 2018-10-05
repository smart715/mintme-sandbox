<?php

namespace App\Tests\Withdraw\Fetcher;

use App\Entity\Crypto;
use App\Entity\User;
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
            [ 'tx_hash' => 123456, 'tx_key' => 654321, 'status' => 'paid' ],
        ];
        $storage = $this->mockStorageAdapter($data);
        $mapper = new WithdrawMapper($storage);
        $history = $mapper->getHistory($this->mockUser());
        /** @var Transaction $transaction */
        $transaction = $history[0];

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($data[0]['tx_hash'], $transaction->getHash());
        $this->assertEquals($data[0]['tx_key'], $transaction->getKey());
        $this->assertEquals($data[0]['status'], $transaction->getStatus()->getStatusCode());
    }

    public function testGetBalance(): void
    {
        $data = [ 'foo' ];
        $storage = $this->mockStorageAdapter($data);
        $mapper = new WithdrawMapper($storage);
        $balance = $mapper->getBalance($this->mockCrypto());

        $this->assertEquals($data, $balance);
    }

    /** @return StorageAdapterInterface|MockObject */
    public function mockStorageAdapter(array $params): StorageAdapterInterface
    {
        $storage = $this->createMock(StorageAdapterInterface::class);

        $storage->method('requestHistory')->willReturn($params);
        $storage->method('requestBalance')->willReturn($params);

        return $storage;
    }

    /** @return Crypto|MockObject */
    public function mockCrypto(): Crypto
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
