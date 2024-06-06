<?php declare(strict_types = 1);

namespace App\Tests\Command\Crypto;

use App\Command\Crypto\AddCryptoNetworkCommand;
use App\Entity\Crypto;
use App\Entity\WrappedCryptoToken;
use App\Manager\CryptoManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\SmartContract\Model\AddTokenResult;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AddCryptoNetworkCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        int $cryptoId,
        int $cryptoDeployId,
        string $crypto,
        string $blockchain,
        bool $isCryptoExist,
        bool $iscryptoDeployExist,
        string $address,
        string $fee,
        bool $isExist,
        bool $alreadyExisted,
        bool $success,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new AddCryptoNetworkCommand(
                $this->mockWrappedCryptoTokenManager($isExist, $success),
                $this->mockCryptoManager(
                    $this->mockCrypto($cryptoId, $crypto),
                    $this->mockCrypto($cryptoDeployId, $blockchain),
                    $isCryptoExist,
                    $iscryptoDeployExist
                ),
                $this->mockEntityManager($success),
                $this->mockContractHandler($alreadyExisted),
                $this->mockMoneyWrapper()
            )
        );

        $command = $application->find('app:add-crypto-network');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--crypto' => $crypto,
            '--blockchain' => $blockchain,
            '--address' => $address,
            '--fee' => $fee,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'crypto does not exist will return an error and status code equals 1' => [
                'cryptoId' => 1,
                'cryptoDeployId' => 2,
                'crypto' => 'TEST',
                'blockchain' => 'ETH',
                'isCryptoExist' => false,
                'iscryptoDeployExist' => true,
                'address' => '0x1234',
                'fee' => '1',
                'isExist' => false,
                'alreadyExisted' => false,
                'success' => false,
                'expected' => 'Crypto TEST symbol doesn\'t exist.',
                'statusCode' => 1,
            ],
            'crypto deploy does not exist will return an error and status code equals 1' => [
                'cryptoId' => 1,
                'cryptoDeployId' => 2,
                'crypto' => 'WEB',
                'blockchain' => 'TEST',
                'isCryptoExist' => true,
                'iscryptoDeployExist' => false,
                'address' => '0x1234',
                'fee' => '1',
                'isExist' => false,
                'alreadyExisted' => false,
                'success' => false,
                'expected' => 'Crypto TEST blockchain symbol doesn\'t exist.',
                'statusCode' => 1,
            ],
            'crypto for provided network already exists will return an error and status code equals 1' => [
                'cryptoId' => 1,
                'cryptoDeployId' => 2,
                'crypto' => 'WEB',
                'blockchain' => 'ETH',
                'isCryptoExist' => true,
                'iscryptoDeployExist' => true,
                'address' => '0x1234',
                'fee' => '1',
                'isExist' => true,
                'alreadyExisted' => false,
                'success' => false,
                'expected' => 'Crypto for provided network already exists. Aborting...',
                'statusCode' => 1,
            ],
            'crypto and blockchain are the same will return an error and status code equals 1' => [
                'cryptoId' => 1,
                'cryptoDeployId' => 1,
                'crypto' => 'WEB',
                'blockchain' => 'WEB',
                'isCryptoExist' => true,
                'iscryptoDeployExist' => true,
                'address' => '0X1234',
                'fee' => '1',
                'isExist' => false,
                'alreadyExisted' => false,
                'success' => false,
                'expected' => 'Crypto and blockchain are the same. Aborting...',
                'statusCode' => 1,
            ],
            'crypto and blockchain are not the same will return a success and status code equals 0' => [
                'cryptoId' => 1,
                'cryptoDeployId' => 2,
                'crypto' => 'WEB',
                'blockchain' => 'ETH',
                'isCryptoExist' => true,
                'iscryptoDeployExist' => true,
                'address' => '0x1234',
                'fee' => '1',
                'isExist' => false,
                'alreadyExisted' => false,
                'success' => true,
                'expected' => 'Crypto network successfully added!',
                'statusCode' => 0,
            ],
            'gateway already has crypto and blockchain added will return a warning and status code equals 0' => [
                'cryptoId' => 1,
                'cryptoDeployId' => 2,
                'crypto' => 'WEB',
                'blockchain' => 'ETH',
                'isCryptoExist' => true,
                'iscryptoDeployExist' => true,
                'address' => '0x1234',
                'fee' => '1',
                'isExist' => false,
                'alreadyExisted' => true,
                'success' => true,
                'expected' => ' Gateway already has WEB/ETH added.',
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
            new AddCryptoNetworkCommand(
                $wrappedCryptoTokenManager,
                $this->mockCryptoManager(
                    $this->mockCrypto(1, 'WEB'),
                    $this->mockCrypto(2, 'ETH'),
                    true,
                    true
                ),
                $entityManager,
                $this->mockContractHandler(false),
                $this->mockMoneyWrapper()
            )
        );

        $command = $application->find('app:add-crypto-network');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--crypto' =>  'WEB',
            '--blockchain' => 'ETH',
            '--address' => '0X1234',
            '--fee' => '1',
        ]);

        $this->assertStringContainsString('Failed to add crypto network', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    private function mockWrappedCryptoTokenManager(bool $isExist, bool $success): WrappedCryptoTokenManagerInterface
    {
        $wrappedCryptoTokenManager = $this->createMock(WrappedCryptoTokenManagerInterface::class);
        $wrappedCryptoTokenManager
            ->method('findByCryptoAndDeploy')
            ->willReturn($isExist ? $this->mockWrappedCryptoToken() : null);
        $wrappedCryptoTokenManager
            ->expects($success ? $this->once() : $this->never())
            ->method('create');

        return $wrappedCryptoTokenManager;
    }

    private function mockCryptoManager(
        Crypto $crypto,
        Crypto $cryptoDeploy,
        bool $isCryptoExist,
        bool $iscryptoDeployExist
    ): CryptoManagerInterface {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager
            ->method('findBySymbol')
            ->willReturnOnConsecutiveCalls(
                $isCryptoExist ? $crypto : null,
                $iscryptoDeployExist ? $cryptoDeploy : null
            );

        return $cryptoManager;
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

    private function mockContractHandler(bool $alreadyExisted): ContractHandlerInterface
    {
        $contractHandler = $this->createMock(ContractHandlerInterface::class);
        $contractHandler
            ->method('addToken')
            ->willReturn($this->mockAddTokenResult($alreadyExisted));

        return $contractHandler;
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper
            ->method('parse')
            ->willReturn(new Money('1', new Currency('WEB')));

        return $moneyWrapper;
    }

    private function mockCrypto(int $id, string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto
            ->method('getId')
            ->willReturn($id);
        $crypto
            ->method('getSymbol')
            ->willReturn($symbol);

        return $crypto;
    }

    private function mockWrappedCryptoToken(): WrappedCryptoToken
    {
        $wrappedCryptoToken = $this->createMock(WrappedCryptoToken::class);
        $wrappedCryptoToken
            ->method('getCrypto')
            ->willReturn($this->mockCrypto(1, 'WEB'));
        $wrappedCryptoToken
            ->method('getCryptoDeploy')
            ->willReturn($this->mockCrypto(2, 'ETH'));

        return $wrappedCryptoToken;
    }

    private function mockAddTokenResult(bool $alreadyExisted): AddTokenResult
    {
        $addTokenResult = $this->createMock(AddTokenResult::class);
        $addTokenResult
            ->method('alreadyExisted')
            ->willReturn($alreadyExisted);

        return $addTokenResult;
    }
}
