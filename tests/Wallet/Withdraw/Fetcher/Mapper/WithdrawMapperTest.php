<?php declare(strict_types = 1);

namespace App\Tests\Wallet\Withdraw\Fetcher\Mapper;

use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Model\Transaction;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\Withdraw\Fetcher\Mapper\WithdrawMapper;
use App\Wallet\Withdraw\Fetcher\Storage\StorageAdapterInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WithdrawMapperTest extends TestCase
{
    public function testGetHistory(): void
    {
        $amount = '21';
        $data = [
            [
                'transactionHash' => '123456',
                'createdDate' => 6543213,
                'status' => 'paid',
                'crypto' => 'web',
                'fee' => 0.1,
                'walletAddress' => '1234567890',
                'amount' => $amount,
            ],
        ];
        $storage = $this->mockStorageAdapter($data);
        $mapper = new WithdrawMapper(
            $storage,
            $this->mockCryptoManager($this->mockCrypto($data[0]['crypto'])),
            $this->mockMoneyWrapper($amount),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager()
        );
        $history = $mapper->getHistory($this->mockUser());
        /** @var Transaction $transaction */
        $transaction = $history[0];

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($data[0]['createdDate'], $transaction->getDate()->getTimestamp());
        $this->assertEquals($data[0]['status'], $transaction->getStatus()->getStatusCode());
        $this->assertEquals($data[0]['crypto'], $transaction->getTradable()->getSymbol());
        $this->assertEquals($data[0]['amount'], $transaction->getAmount()->getAmount());
    }

    public function testGetBalance(): void
    {
        $amount = '123';
        $data = [ $amount ];
        $storage = $this->mockStorageAdapter($data);
        $mapper = new WithdrawMapper(
            $storage,
            $this->mockCryptoManager($this->mockCrypto()),
            $this->mockMoneyWrapper($amount),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager()
        );
        $balance = $mapper->getBalance($this->mockCrypto(), $this->mockCrypto());

        $this->assertEquals($data[0], $balance->getAmount());
    }

    /** @return MockObject|MoneyWrapperInterface */
    private function mockMoneyWrapper(string $data): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);

        $moneyWrapper->method('parse')->willReturn(new Money($data, new Currency(Symbols::TOK)));

        return $moneyWrapper;
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
        $storage->method('requestBalance')->willReturn("test");

        return $storage;
    }

    /** @return Crypto|MockObject */
    public function mockCrypto(string $symbol = 'web'): Crypto
    {
        $crypto = $this->createMock(Crypto::class);

        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }

    /** @return User|MockObject */
    public function mockUser(): User
    {
        $user = $this->createMock(User::class);

        $user->method('getId')->willReturn(1);

        return $user;
    }

    public function mockLimitHistoryConfig(): LimitHistoryConfig
    {
        $config = $this->createMock(LimitHistoryConfig::class);
        $date = (new \DateTimeImmutable())->setTimestamp(1);

        $config
            ->method('getFromDate')
            ->willReturn($date);

        return $config;
    }

    private function mockWrappedCryptoTokenManager(): WrappedCryptoTokenManagerInterface
    {
        return $this->createMock(WrappedCryptoTokenManagerInterface::class);
    }
}
