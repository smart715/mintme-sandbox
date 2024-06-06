<?php declare(strict_types = 1);

namespace App\Tests\Command\Crypto;

use App\Command\Crypto\ShowCryptoNetworksCommand;
use App\Entity\Crypto;
use App\Entity\WrappedCryptoToken;
use App\Repository\WrappedCryptoTokenRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ShowCryptoNetworksCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        array $wrappedCryptoTokens,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new ShowCryptoNetworksCommand(
                $this->mockWrappedCryptoTokenRepository($wrappedCryptoTokens),
                $this->mockMoneyWrapper()
            )
        );

        $command = $application->find('app:show-crypto-networks');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'no wrapped crypto tokens will return an empty table and status code equals 0' => [
                'wrappedCryptoTokens' => [],
                'expected' => '',
                'statusCode' => 0,
            ],
            'wrapped crypto tokens are set will return a table and status code equals 0' => [
                'wrappedCryptoTokens' => [$this->mockWrappedCryptoToken()],
                'expected' => '| WEB    | ETH        | 0x1234  | 1 ETH | Enabled |',
                'statusCode' => 0,
            ],
        ];
    }

    private function mockWrappedCryptoTokenRepository(array $wrappedCryptoTokens): WrappedCryptoTokenRepository
    {
        $wrappedCryptoTokenRepository = $this->createMock(WrappedCryptoTokenRepository::class);
        $wrappedCryptoTokenRepository
            ->method('findBy')
            ->willReturn($wrappedCryptoTokens);

        return $wrappedCryptoTokenRepository;
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

    private function mockWrappedCryptoToken(): WrappedCryptoToken
    {
        $wrappedCryptoToken = $this->createMock(WrappedCryptoToken::class);
        $wrappedCryptoToken
            ->method('getCrypto')
            ->willReturn($this->mockCrypto('WEB'));
        $wrappedCryptoToken
            ->method('getCryptoDeploy')
            ->willReturn($this->mockCrypto('ETH'));
        $wrappedCryptoToken
            ->method('getAddress')
            ->willReturn('0x1234');
        $wrappedCryptoToken
            ->method('getFee')
            ->willReturn(new Money('1', new Currency('TOK')));
        $wrappedCryptoToken
            ->method('getFeeCurrency')
            ->willReturn('ETH');
        $wrappedCryptoToken
            ->method('isEnabled')
            ->willReturn(true);

        return $wrappedCryptoToken;
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto
            ->method('getSymbol')
            ->willReturn($symbol);

        return $crypto;
    }
}
