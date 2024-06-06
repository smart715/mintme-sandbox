<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\BlockTokenCommand;
use App\Command\CancelOrdersForOldBlockedCommand;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CancelOrdersForOldBlockedCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    
    public function setUp(): void
    {
        $cancelCommand = new CancelOrdersForOldBlockedCommand(
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockTokenRepository(),
            $this->mockBlockTokenCommand(),
            $this->mockUserRepository(),
        );

        $kernel = self::bootKernel();
        $app = new Application($kernel);
        $app->add($cancelCommand);

        $command = $app->find('app:cancel-orders');
        $this->commandTester = new CommandTester($command);
    }
    
    public function testExecute(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString(
            'orders for old user/token blocked has been cancelled',
            $output
        );
    }

    private function mockBlockTokenCommand(): BlockTokenCommand
    {
        $blockCommand = $this->createMock(BlockTokenCommand::class);
        $blockCommand
            ->expects($this->exactly(2))
            ->method('cancelCoinOrders');

        $blockCommand
            ->expects($this->exactly(4))
            ->method('cancelTokenOrders');

        return $blockCommand;
    }

    private function mockMarketFactory(): MarketFactoryInterface
    {
        $marketFactory = $this->createMock(MarketFactoryInterface::class);
        $marketFactory
            ->expects($this->exactly(2))
            ->method('getCoinMarkets');

        $marketFactory
            ->expects($this->exactly(2))
            ->method('create');

        return $marketFactory;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $manager = $this->createMock(CryptoManagerInterface::class);
        $manager
            ->method('findBySymbol')
            ->with('WEB')
            ->willReturn($this->mockCrypto());

        return $manager;
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockUserRepository(): UserRepository
    {
        $repository = $this->createMock(UserRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['isBlocked' => true])
            ->willReturn([$this->mockUser(), $this->mockUser()]);

        return $repository;
    }

    private function mockTokenRepository(): TokenRepository
    {
        $tokenRepository = $this->createMock(TokenRepository::class);
        $tokenRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['isBlocked' => true])
            ->willReturn([$this->mockToken(), $this->mockToken()]);

        return $tokenRepository;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token
            ->method('getCryptoSymbol')
            ->willReturn('WEB');

        return $token;
    }
}
