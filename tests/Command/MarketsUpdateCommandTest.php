<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\MarketsUpdateCommand;
use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockInterface;

class MarketsUpdateCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        bool $isLockAcquired,
        string $market,
        bool $cron,
        bool $isBaseExist,
        bool $isQuoteExist,
        int $invokedCount,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new MarketsUpdateCommand(
                $this->mockMarketStatusManager(),
                $this->mockMarketFactory(),
                $this->mockCryptoManager($isBaseExist, $isQuoteExist),
                $this->mockTokenManager($isQuoteExist),
                $this->mockRebrandingConverter($market),
                $this->mockLockFactory($isLockAcquired),
                $this->mockEntityManager($invokedCount, $market)
            )
        );

        $command = $application->find('app:markets:update');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'market' => $market,
            '--cron' => $cron,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'lock acquired equals false will return an empty message and status code equals 0' => [
                'isLockAcquired' => false,
                'market' => 'MINTME/BTC',
                'cron' => false,
                'isBaseExist' => true,
                'isQuoteExist' => true,
                'invokedCount' => 0,
                'expected' => '',
                'statusCode' => 0,
            ],
            'market is not valid will return an error and status code equals 1' => [
                'isLockAcquired' => true,
                'market' => 'MINTME',
                'cron' => false,
                'isBaseExist' => true,
                'isQuoteExist' => true,
                'invokedCount' => 0,
                'expected' => 'Invalid argument market',
                'statusCode' => 1,
            ],
            'base crypto does not exist will return an error and status code equals 1' => [
                'isLockAcquired' => true,
                'market' => 'MINTME/BTC',
                'cron' => false,
                'isBaseExist' => false,
                'isQuoteExist' => true,
                'invokedCount' => 0,
                'expected' => 'Base crypto not found',
                'statusCode' => 1,
            ],
            'quote crypto does not exist will return an error and status code equals 1' => [
                'isLockAcquired' => true,
                'market' => 'MINTME/BTC',
                'cron' => false,
                'isBaseExist' => true,
                'isQuoteExist' => false,
                'invokedCount' => 0,
                'expected' => 'Quote crypto or token not found',
                'statusCode' => 1,
            ],
            'market exists will return a success and status code equals 0' => [
                'isLockAcquired' => true,
                'market' => 'MINTME/BTC',
                'cron' => false,
                'isBaseExist' => true,
                'isQuoteExist' => true,
                'invokedCount' => 0,
                'expected' => 'Market updated',
                'statusCode' => 0,
            ],
            'market does not exist and cron is set to false will return a success and status code equals 0' => [
                'isLockAcquired' => true,
                'market' => '',
                'cron' => false,
                'isBaseExist' => true,
                'isQuoteExist' => true,
                'invokedCount' => 2,
                'expected' => 'Markets updated',
                'statusCode' => 0,
            ],
            'market does not exist and cron is set to true will return a success and status code equals 0' => [
                'isLockAcquired' => true,
                'market' => '',
                'cron' => true,
                'isBaseExist' => true,
                'isQuoteExist' => true,
                'invokedCount' => 2,
                'expected' => 'Markets updated',
                'statusCode' => 0,
            ],
        ];
    }

    public function testExecuteWithException(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);


        $marketStatusManager = $this->createMock(MarketStatusManagerInterface::class);
        $marketStatusManager
            ->method('updateMarketStatus')
            ->willThrowException(new Exception());

        $application->add(
            new MarketsUpdateCommand(
                $marketStatusManager,
                $this->mockMarketFactory(),
                $this->mockCryptoManager(true, true),
                $this->mockTokenManager(true),
                $this->mockRebrandingConverter('MINTME/BTC'),
                $this->mockLockFactory(true),
                $this->mockEntityManager(0, 'MINTME/BTC')
            )
        );

        $command = $application->find('app:markets:update');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'market' => 'MINTME/BTC',
            '--cron' => false,
        ]);

        $this->assertStringContainsString('Error:', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    private function mockMarketStatusManager(): MarketStatusManagerInterface
    {
        $marketStatusManager = $this->createMock(MarketStatusManagerInterface::class);
        $marketStatusManager
            ->method('getExpired')
            ->willReturn([$this->mockMarketStatus()]);

        return $marketStatusManager;
    }

    private function mockMarketFactory(): MarketFactoryInterface
    {
        $marketFactory = $this->createMock(MarketFactoryInterface::class);
        $marketFactory
            ->method('create')
            ->willReturn($this->mockMarket());
        $marketFactory
            ->method('createAll')
            ->willReturn([$this->mockMarket()]);

        return $marketFactory;
    }

    private function mockCryptoManager(
        bool $isBaseExist,
        bool $isQuoteExist
    ): CryptoManagerInterface {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager
            ->method('findBySymbol')
            ->willReturnOnConsecutiveCalls(
                $isBaseExist ? $this->mockCrypto() : null,
                $isQuoteExist ? $this->mockCrypto() : null
            );

        return $cryptoManager;
    }

    private function mockTokenManager(bool $isQuoteExist): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->method('findByName')
            ->willReturn($isQuoteExist ? $this->mockToken() : null);

        return $tokenManager;
    }

    private function mockRebrandingConverter(string $market): RebrandingConverterInterface
    {
        $rebrandingConverter = $this->createMock(RebrandingConverterInterface::class);
        $rebrandingConverter
            ->method('reverseConvert')
            ->willReturn($market);

        return $rebrandingConverter;
    }

    private function mockLock(bool $isLockAcquired): LockInterface
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->method('acquire')
            ->wilLReturn($isLockAcquired);
        $lock
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('release');

        return $lock;
    }

    private function mockLockFactory(bool $isLockAcquired): LockFactory
    {
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory
            ->expects($this->once())
            ->method('createFileBasedLock')
            ->willReturn($this->mockLock($isLockAcquired));

        return $lockFactory;
    }

    private function mockEntityManager(int $invokedCount, string $market): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly($invokedCount))
            ->method('beginTransaction');
        $entityManager
            ->expects($market ? $this->never() : $this->once())
            ->method('commit');
        $entityManager
            ->expects($market ? $this->never() : $this->once())
            ->method('clear');

        return $entityManager;
    }

    private function mockMarket(): Market
    {
        return $this->createMock(Market::class);
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    private function mockMarketStatus(): MarketStatus
    {
        $marketStatus = $this->createMock(MarketStatus::class);
        $marketStatus
            ->method('getCrypto')
            ->willReturn($this->mockCrypto());
        $marketStatus
            ->method('getQuote')
            ->willReturn($this->mockCrypto());

        return $marketStatus;
    }
}
