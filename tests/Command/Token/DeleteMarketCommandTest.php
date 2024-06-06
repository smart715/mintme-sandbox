<?php declare(strict_types = 1);

namespace App\Tests\Command\Token;

use App\Command\Token\DeleteMarketCommand;
use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Entity\TokenCrypto;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenCryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteMarketCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        string $tokenName,
        string $cryptoSymbol,
        bool $payback,
        bool $tokenManagerFindByName,
        bool $cryptoManagerFindBySymbol,
        bool $tokenCryptoManagerGetByCryptoAndToken,
        bool $getMarketStatus,
        int $flushCount,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $crypto = $this->mockCrypto($cryptoSymbol);

        $application->add(
            new DeleteMarketCommand(
                $this->mockEntityManager(
                    $tokenManagerFindByName && $cryptoManagerFindBySymbol,
                    $flushCount
                ),
                $this->mockTokenManager($tokenName, $tokenManagerFindByName),
                $this->mockCryptoManager($crypto, $cryptoManagerFindBySymbol),
                $this->mockMarketStatusManager($getMarketStatus),
                $this->mockTokenCryptoManager(
                    $tokenCryptoManagerGetByCryptoAndToken,
                    $this->mockCrypto($cryptoSymbol)
                ),
                $this->mockMoneyWrapper(),
                $this->mockBalanceHandler($payback, $crypto)
            )
        );

        $command = $application->find('app:delete-market');
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

    public function testThrowException(): void
    {
        $tokenName = 'randomName';
        $cryptoSymbol = 'testSymbol';
        $crypto = $this->mockCrypto($cryptoSymbol);

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('beginTransaction');
        $em
            ->expects($this->once())
            ->method('rollback');

        $tokenCryptoManager = $this->createMock(TokenCryptoManagerInterface::class);
        $tokenCryptoManager
            ->method('getByCryptoAndToken')
            ->willThrowException(new \Exception('message'));

        $application->add(
            new DeleteMarketCommand(
                $em,
                $this->mockTokenManager($tokenName, true),
                $this->mockCryptoManager($crypto, true),
                $this->createMock(MarketStatusManagerInterface::class),
                $tokenCryptoManager,
                $this->createMock(MoneyWrapperInterface::class),
                $this->createMock(BalanceHandlerInterface::class)
            )
        );

        $command = $application->find('app:delete-market');
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

    public function executeDataProvider(): array
    {
        return [
            "Token doesn't exists" => [
                'tokenName' => 'undefined',
                'cryptoSymbol' => 'randomSymbol',
                'payback' => false,
                'tokenManagerFindByName' => false,
                'cryptoManagerFindBySymbol' => true,
                'tokenCryptoManagerGetByCryptoAndToken' => false,
                'getMarketStatus' => false,
                'flushCount' => 0,
                'expected' => "Token with provided name doesn't exists. Provided name: undefined",
                'statusCode' => 1,
            ],
            "Crypto doesn't exist" => [
                'tokenName' => 'randomName',
                'cryptoSymbol' => 'undefined',
                'payback' => false,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => false,
                'tokenCryptoManagerGetByCryptoAndToken' => false,
                'getMarketStatus' => false,
                'flushCount' => 0,
                'expected' => "Crypto with provided symbol doesn't exists. Provided symbol: undefined",
                'statusCode' => 1,
            ],
            "TokenCrypto doesn't exist" => [
                'tokenName' => 'tok',
                'cryptoSymbol' => 'sym',
                'payback' => false,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokenCryptoManagerGetByCryptoAndToken' => false,
                'getMarketStatus' => false,
                'flushCount' => 0,
                'expected' => "Token crypto for sym/tok doesn't exists. Skipping...",
                'statusCode' => 0,
            ],
            "TokenCrypto exists" => [
                'tokenName' => 'tok',
                'cryptoSymbol' => 'sym',
                'payback' => false,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokenCryptoManagerGetByCryptoAndToken' => true,
                'getMarketStatus' => false,
                'flushCount' => 1,
                'expected' => "Token crypto for sym/tok was removed",
                'statusCode' => 0,
            ],
            "Market status doesn't exist" => [
                'tokenName' => 'marketTok',
                'cryptoSymbol' => 'marketSym',
                'payback' => false,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokenCryptoManagerGetByCryptoAndToken' => false,
                'getMarketStatus' => false,
                'flushCount' => 0,
                'expected' => "Market status for marketSym/marketTok doesn't exists. Skipping...",
                'statusCode' => 0,
            ],
            "Market status exists" => [
                'tokenName' => 'marketTok',
                'cryptoSymbol' => 'marketSym',
                'payback' => false,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokenCryptoManagerGetByCryptoAndToken' => false,
                'getMarketStatus' => true,
                'flushCount' => 1,
                'expected' => "Market status for marketSym/marketTok was removed",
                'statusCode' => 0,
            ],
            "Payback option" => [
                'tokenName' => 'paybackTok',
                'cryptoSymbol' => 'paybackSym',
                'payback' => true,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokenCryptoManagerGetByCryptoAndToken' => true,
                'getMarketStatus' => false,
                'flushCount' => 1,
                'expected' => "Funds for market creation 1 was returned to user",
                'statusCode' => 0,
            ],
            "Successfully removed when all params exist" => [
                'tokenName' => 'allTok',
                'cryptoSymbol' => 'allSym',
                'payback' => true,
                'tokenManagerFindByName' => true,
                'cryptoManagerFindBySymbol' => true,
                'tokenCryptoManagerGetByCryptoAndToken' => true,
                'getMarketStatus' => true,
                'flushCount' => 2,
                'expected' => "Market successfully reverted.",
                'statusCode' => 0,
            ],
        ];
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

        $token
            ->method('getOwner')
            ->willReturn($this->createMock(User::class));

        return $token;
    }

    private function mockCryptoManager(Crypto $crypto, bool $findBySymbol): CryptoManagerInterface
    {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);

        $cryptoManager
            ->method('findBySymbol')
            ->willReturn($findBySymbol ? $crypto : null);

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

    private function mockEntityManager(bool $beginTrans, int $flushCount): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em
            ->expects($beginTrans ? $this->once() : $this->never())
            ->method('beginTransaction');

        $em
            ->expects($this->exactly($flushCount))
            ->method('remove');

        $em
            ->expects($this->exactly($flushCount))
            ->method('flush');

        return $em;
    }

    private function mockTokenCryptoManager(
        bool $getByCryptoAndToken,
        Crypto $crypto
    ): TokenCryptoManagerInterface {
        $manager = $this->createMock(TokenCryptoManagerInterface::class);
        $tokenCrypto = $this->createMock(TokenCrypto::class);

        $tokenCrypto
            ->method('getCryptoCost')
            ->willReturn($crypto);

        $tokenCrypto
            ->method('getCost')
            ->willReturn(new Money('1', new Currency($crypto->getSymbol())));

        $manager
            ->method('getByCryptoAndToken')
            ->willReturn($getByCryptoAndToken ? $tokenCrypto : null);

        return $manager;
    }

    private function mockMarketStatusManager(bool $getMarketStatus): MarketStatusManagerInterface
    {
        $manager = $this->createMock(MarketStatusManagerInterface::class);
        $ms = $this->createMock(MarketStatus::class);

        $manager
            ->method('getMarketStatus')
            ->willReturn($getMarketStatus ? $ms : null);

        return $manager;
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper
            ->method('format')
            ->willReturnCallback(function (Money $money): string {
                return $money->getAmount();
            });

        return $moneyWrapper;
    }

    private function mockBalanceHandler(bool $payback, Crypto $crypto): BalanceHandlerInterface
    {
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $bh
            ->expects($payback ? $this->once() : $this->never())
            ->method('deposit')
            ->with(
                $this->anything(),
                $crypto,
                new Money('1', new Currency($crypto->getSymbol()))
            );

        return $bh;
    }
}
