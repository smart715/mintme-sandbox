<?php declare(strict_types = 1);

namespace App\Tests\Command\Token;

use App\Command\Token\DeleteDeployCommand;
use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\TokenDeployRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteDeployCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        string $tokenName,
        string $cryptoSymbol,
        bool $payback,
        bool $tokenManagerFindByName,
        bool $cryptoManagerFindBySymbol,
        bool $tokDeployFindOneBy,
        bool $getMarketStatus,
        int $removeCount,
        int $flushCount,
        int $persistCount,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new DeleteDeployCommand(
                $this->mockEntityManager(
                    $tokenManagerFindByName && $cryptoManagerFindBySymbol,
                    $removeCount,
                    $persistCount,
                    $flushCount
                ),
                $this->mockTokenManager($tokenName, $tokenManagerFindByName),
                $this->mockCryptoManager($cryptoSymbol, $cryptoManagerFindBySymbol),
                $this->mockMarketStatusManager($getMarketStatus),
                $this->mockTokenDeployRepository($tokDeployFindOneBy, $this->mockCrypto($cryptoSymbol)),
                $this->createMock(MoneyWrapperInterface::class),
                $this->mockBalanceHandler($payback, $cryptoSymbol),
            )
        );

        $command = $application->find('app:delete-deploy');
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [
                "--token" => $tokenName,
                "--crypto" => $cryptoSymbol,
                "--payback" => $payback,
            ]
        );

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            "Token doesn't exists" => [
                'tokenName' => 'undefined',
                'cryptoSymbol' => 'undefined',
                'payback' => false,
                'tokenManagerFindByName' => false,
                'cryptoFindByName' => false,
                'tokDeployFindOneBy' => false,
                'getMarketStatus' => false,
                'removeCount' => 0,
                'flushCount' => 0,
                'persistCount' => 0,
                'expected' => "Token with provided name doesn't exists. Provided name: undefined",
                'statusCode' => 1,
            ],
            "Crypto doesn't exists" => [
                'tokenName' => 'abcToken',
                'cryptoSymbol' => 'UKNOWNCOIN',
                'payback' => false,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => false,
                'tokDeployFindOneBy' => false,
                'getMarketStatus' => false,
                'removeCount' => 0,
                'flushCount' => 0,
                'persistCount' => 0,
                'expected' => "Crypto with provided symbol doesn't exists. Provided symbol: UKNOWNCOIN",
                'statusCode' => 1,
            ],
            "Token deploy doesn't exists" => [
                'tokenName' => 'abcToken',
                'cryptoSymbol' => 'abc',
                'payback' => false,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokDeployFindOneBy' => false,
                'getMarketStatus' => false,
                'removeCount' => 0,
                'flushCount' => 0,
                'persistCount' => 0,
                'expected' => "Token deploy for abcToken token doesn't exists. Skipping...",
                'statusCode' => 0,
            ],
            "Token deploy exists" => [
                'tokenName' => 'abcToken',
                'cryptoSymbol' => 'abc',
                'payback' => false,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokDeployFindOneBy' => true,
                'getMarketStatus' => false,
                'removeCount' => 1,
                'flushCount' => 1,
                'persistCount' => 0,
                'expected' => "Token deploy for abcToken was removed",
                'statusCode' => 0,
            ],
            "Market status doesn't exists" => [
                'tokenName' => 'marketTok',
                'cryptoSymbol' => 'statusSym',
                'payback' => false,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokDeployFindOneBy' => false,
                'getMarketStatus' => false,
                'removeCount' => 0,
                'flushCount' => 0,
                'persistCount' => 0,
                'expected' => "Market status for marketTok token and statusSym symbol doesn't",
                'statusCode' => 0,
            ],
            "Market status exists" => [
                'tokenName' => 'marketTok',
                'cryptoSymbol' => 'statusSym',
                'payback' => false,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokDeployFindOneBy' => false,
                'getMarketStatus' => true,
                'removeCount' => 0,
                'flushCount' => 1,
                'persistCount' => 1,
                'expected' => "Token networks for marketTok and crypto statusSym were updated",
                'statusCode' => 0,
            ],
            "Payback option" => [
                'tokenName' => 'paybackTok',
                'cryptoSymbol' => 'paybackSym',
                'payback' => true,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokDeployFindOneBy' => true,
                'getMarketStatus' => false,
                'removeCount' => 1,
                'flushCount' => 1,
                'persistCount' => 0,
                'expected' => "Payback for paybackTok token and paybackSym symbol was successfully finished",
                'statusCode' => 0,
            ],
            "Successfully removed when all params exist" => [
                'tokenName' => 'paybackTok',
                'cryptoSymbol' => 'paybackSym',
                'payback' => true,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokDeployFindOneBy' => true,
                'getMarketStatus' => true,
                'removeCount' => 1,
                'flushCount' => 2,
                'persistCount' => 1,
                'expected' => "Token deploy was successfully removed.",
                'statusCode' => 0,
            ],
        ];
    }

    public function testThrowException(): void
    {
        $tokenName = 'randomName';
        $cryptoSymbol = 'CRYPTO';

        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('beginTransaction');
        $em
            ->expects($this->once())
            ->method('rollback');

        $tokDepRepo = $this->createMock(TokenDeployRepository::class);
        $tokDepRepo
            ->expects($this->once())
            ->method('findOneBy')
            ->willThrowException(new \Exception('message'));

        $application->add(
            new DeleteDeployCommand(
                $em,
                $this->mockTokenManager($tokenName, true),
                $this->mockCryptoManager($cryptoSymbol, true),
                $this->createMock(MarketStatusManagerInterface::class),
                $tokDepRepo,
                $this->createMock(MoneyWrapperInterface::class),
                $this->createMock(BalanceHandlerInterface::class)
            )
        );

        $command = $application->find('app:delete-deploy');
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [
                "--token" => $tokenName,
                "--crypto" => $cryptoSymbol,
                "--payback" => false,
            ]
        );

        $this->assertStringContainsString(
            "Something went wrong, aborting. Error: message",
            $commandTester->getDisplay()
        );

        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    private function mockTokenManager(string $tokenName, bool $findByName): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);

        $tokenManager
            ->expects($this->once())
            ->method('findByName')
            ->willReturn($findByName ? $this->mockToken($tokenName) : null);

        return $tokenManager;
    }

    private function mockToken(string $tokenName): Token
    {
        $token = $this->createMock(Token::class);

        $token
            ->method('getName')
            ->willReturn($tokenName);

        return $token;
    }

    private function mockCryptoManager(string $cryptoSymbol, bool $findBySymbol): CryptoManagerInterface
    {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);

        $cryptoManager
            ->method('findBySymbol')
            ->willReturn($findBySymbol ? $this->mockCrypto($cryptoSymbol) : null);

        return $cryptoManager;
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);

        $crypto
            ->method('getSymbol')
            ->willReturn($symbol);

        return $crypto;
    }

    private function mockEntityManager(
        bool $beginTrans,
        int $removeCount,
        int $persistCount,
        int $flushCount
    ): EntityManagerInterface {
        $em = $this->createMock(EntityManagerInterface::class);

        $em
            ->expects($beginTrans ? $this->once() : $this->never())
            ->method('beginTransaction');

        $em
            ->expects($this->exactly($removeCount))
            ->method('remove');

        $em
            ->expects($this->exactly($persistCount))
            ->method('persist');

        $em
            ->expects($this->exactly($flushCount))
            ->method('flush');

        return $em;
    }

    private function mockTokenDeployRepository(bool $finOneBy, Crypto $crypto): TokenDeployRepository
    {
        $repo = $this->createMock(TokenDeployRepository::class);

        $repo
            ->method('findOneBy')
            ->willReturn($finOneBy ? $this->mockTokenDeploy($crypto) : null);

        return $repo;
    }

    private function mockTokenDeploy(Crypto $crypto): TokenDeploy
    {
        $tokDeploy = $this->createMock(TokenDeploy::class);

        $tokDeploy
            ->method('getDeployCost')
            ->willReturn('1');

        $tokDeploy
            ->method('getCrypto')
            ->willReturn($crypto);

        return $tokDeploy;
    }

    private function mockMarketStatusManager(bool $getMarketStatus): MarketStatusManagerInterface
    {
        $manager = $this->createMock(MarketStatusManagerInterface::class);
        $ms = $this->createMock(MarketStatus::class);

        if ($getMarketStatus) {
            $ms
                ->expects($this->once())
                ->method('setNetworks');
        }

        $manager
            ->method('getMarketStatus')
            ->willReturn($getMarketStatus ? $ms : null);

        return $manager;
    }

    private function mockBalanceHandler(bool $payback, string $symbol): BalanceHandlerInterface
    {
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $bh
            ->expects($payback ? $this->once() : $this->never())
            ->method('deposit')
            ->with(
                $this->anything(),
                $this->anything(),
                new Money('1', new Currency($symbol))
            );

        return $bh;
    }
}
