<?php declare(strict_types = 1);

namespace App\Tests\Command\Crypto;

use App\Command\Crypto\UpdateCryptoNetworkStatus;
use App\Entity\Crypto;
use App\Entity\WrappedCryptoToken;
use App\Manager\CryptoManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCryptoNetworkStatusTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        string $crypto,
        ?string $blockchain,
        string $enabled,
        bool $isCryptoExist,
        bool $isCryptoDeployExist,
        bool $isExist,
        bool $isEnabled,
        bool $success,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new UpdateCryptoNetworkStatus(
                $this->mockWrappedCryptoTokenManager($isExist, $isEnabled, $blockchain, $success),
                $this->mockContractHandler($isExist),
                $this->mockEntityManager($success),
                $this->mockCryptoManager(
                    $this->mockCrypto($crypto),
                    $this->mockCrypto($blockchain),
                    $isCryptoExist,
                    $isCryptoDeployExist
                ),
            )
        );

        $command = $application->find('app:update-crypto-network-status');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--crypto' => $crypto,
            '--blockchain' => $blockchain,
            '--enabled' => $enabled,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'crypto does not exist will return an error and status code equals 1' => [
                'crypto' => 'TEST',
                'blockchain' => 'ETH',
                'enabled' => '0',
                'isCryptoExist' => false,
                'isCryptoDeployExist' => true,
                'isExist' => false,
                'isEnabled' => false,
                'success' => false,
                'expected' => 'Crypto TEST symbol doesn\'t exist.',
                'statusCode' => 1,
            ],
            'wrong crypto blockchain symbol will return an error and status code equals 1' => [
                'crypto' => 'WEB',
                'blockchain' => 'TEST',
                'enabled' => '0',
                'isCryptoExist' => true,
                'isCryptoDeployExist' => false,
                'isExist' => false,
                'isEnabled' => false,
                'success' => false,
                'expected' => 'Wrong crypto TEST blockchain symbol provided. It doesn\'t exists in db.',
                'statusCode' => 1,
            ],
            'crypto does not exists in a blockchain will return an error and status code equals 1' => [
                'crypto' => 'WEB',
                'blockchain' => 'ETH',
                'enabled' => '0',
                'isCryptoExist' => true,
                'isCryptoDeployExist' => true,
                'isExist' => false,
                'isEnabled' => false,
                'success' => false,
                'expected' => 'Crypto WEB doesn\'t exists in ETH blockchain. Aborting...',
                'statusCode' => 1,
            ],
            'crypto network is disabled and enabled option is set to 0 will return a warning and status code equals 1' => [
                'crypto' => 'WEB',
                'blockchain' => 'ETH',
                'enabled' => '0',
                'isCryptoExist' => true,
                'isCryptoDeployExist' => true,
                'isExist' => true,
                'isEnabled' => false,
                'success' => false,
                'expected' => 'Crypto network already disabled. Aborting...',
                'statusCode' => 1,
            ],
            'crypto network is enabled and enabled option is set to 1 will return a warning and status code equals 1' => [
                'crypto' => 'WEB',
                'blockchain' => 'ETH',
                'enabled' => '1',
                'isCryptoExist' => true,
                'isCryptoDeployExist' => true,
                'isExist' => true,
                'isEnabled' => true,
                'success' => false,
                'expected' => 'Crypto network already enabled. Aborting...',
                'statusCode' => 1,
            ],
            'crypto network is disabled and enabled option is set to 1 will return a success and status code equals 0' => [
                'crypto' => 'WEB',
                'blockchain' => 'ETH',
                'enabled' => '1',
                'isCryptoExist' => true,
                'isCryptoDeployExist' => true,
                'isExist' => true,
                'isEnabled' => false,
                'success' => true,
                'expected' => 'ETH network for WEB crypto was enabled',
                'statusCode' => 0,
            ],
            'crypto network is enabled and enabled option is set to 0 will return a success and status code equals 0' => [
                'crypto' => 'WEB',
                'blockchain' => 'ETH',
                'enabled' => '0',
                'isCryptoExist' => true,
                'isCryptoDeployExist' => true,
                'isExist' => true,
                'isEnabled' => true,
                'success' => true,
                'expected' => 'ETH network for WEB crypto was disabled',
                'statusCode' => 0,
            ],
            'blockchain symbol is not set and enabled option is set to 0 will return a success and status code equals 0' => [
                'crypto' => 'WEB',
                'blockchain' => null,
                'enabled' => '0',
                'isCryptoExist' => true,
                'isCryptoDeployExist' => false,
                'isExist' => true,
                'isEnabled' => true,
                'success' => true,
                'expected' => 'All networks for WEB crypto was disabled',
                'statusCode' => 0,
            ],
            'blockchain symbol is not set and enabled option is set to 1 will return a success and status code equals 0' => [
                'crypto' => 'WEB',
                'blockchain' => null,
                'enabled' => '1',
                'isCryptoExist' => true,
                'isCryptoDeployExist' => false,
                'isExist' => true,
                'isEnabled' => false,
                'success' => true,
                'expected' => 'All networks for WEB crypto was enabled',
                'statusCode' => 0,
            ],
        ];
    }

    public function testExecuteWithException(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $wrappedCryptoTokenManager = $this->createMock(WrappedCryptoTokenManagerInterface::class);
        $wrappedCryptoTokenManager
            ->method('findByCryptoAndDeploy')
            ->willThrowException(new \Exception());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('beginTransaction');
        $entityManager
            ->expects($this->once())
            ->method('rollback');

        $application->add(
            new UpdateCryptoNetworkStatus(
                $wrappedCryptoTokenManager,
                $this->mockContractHandler(false),
                $entityManager,
                $this->mockCryptoManager(
                    $this->mockCrypto('WEB'),
                    $this->mockCrypto('ETH'),
                    true,
                    true
                ),
            )
        );

        $command = $application->find('app:update-crypto-network-status');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--crypto' => 'WEB',
            '--blockchain' => 'ETH',
            '--enabled' => '0',
        ]);

        $this->assertStringContainsString('Failed to update crypto network status', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    private function mockWrappedCryptoTokenManager(
        bool $isExist,
        bool $isEnabled,
        ?string $blockchain,
        bool $success
    ): WrappedCryptoTokenManagerInterface {
        $wrappedCryptoTokenManager = $this->createMock(WrappedCryptoTokenManagerInterface::class);
        $wrappedCryptoTokenManager
            ->method('findByCryptoAndDeploy')
            ->willReturn($isExist ? $this->mockWrappedCryptoToken($isEnabled) : null);
        $wrappedCryptoTokenManager
            ->expects($blockchain && $success ? $this->once() : $this->never())
            ->method('updateWrappedCryptoTokenStatus');
        $wrappedCryptoTokenManager
            ->expects(!$blockchain && $success ? $this->once() : $this->never())
            ->method('updateCryptoStatuses');

        return $wrappedCryptoTokenManager;
    }

    private function mockContractHandler(bool $isExist): ContractHandlerInterface
    {
        $contractHandler = $this->createMock(ContractHandlerInterface::class);
        $contractHandler
            ->expects($isExist ? $this->once() : $this->never())
            ->method('updateTokenStatus');

        return $contractHandler;
    }
    private function mockEntityManager(bool $success): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('beginTransaction');
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('commit');

        return $entityManager;
    }

    private function mockCryptoManager(
        Crypto $crypto,
        Crypto $cryptoDeploy,
        bool $isCryptoExist,
        bool $isCryptoDeployExist
    ): CryptoManagerInterface {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager
            ->method('findBySymbol')
            ->willReturnOnConsecutiveCalls(
                $isCryptoExist ? $crypto : null,
                $isCryptoDeployExist ? $cryptoDeploy : null
            );

        return $cryptoManager;
    }

    private function mockCrypto(?string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);

        if ($symbol) {
            $crypto
                ->method('getSymbol')
                ->willReturn($symbol);
        }

        return $crypto;
    }

    private function mockWrappedCryptoToken(bool $isEnabled): WrappedCryptoToken
    {
        $wrappedCryptoToken = $this->createMock(WrappedCryptoToken::class);
        $wrappedCryptoToken
            ->method('isEnabled')
            ->willReturn($isEnabled);

        return $wrappedCryptoToken;
    }
}
