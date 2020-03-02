<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Donation;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Donation\DonationHandler;
use App\Exchange\Market;
use App\Tests\MockMoneyWrapper;
use App\Utils\Converter\MarketNameConverterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DonationHandlerTest extends TestCase
{

    use MockMoneyWrapper;

    /** @dataProvider getCheckDonationProvider */
    public function testCheckDonation(bool $hasError, ?string $expectedToReceive): void
    {
        $base = $this->mockCrypto();
        $base->method('getSymbol')->willReturn('WEB');

        $quote = $this->mockToken();
        $quote->method('getSymbol')->willReturn('TOK000000000123');

        $market = new Market($base, $quote);

        /** @var MarketNameConverterInterface|MockObject $marketNameConverter */
        $marketNameConverter = $this->createMock(MarketNameConverterInterface::class);
        $marketNameConverter
            ->method('convert')
            ->with($market)
            ->willReturn('TOK000000000123WEB');

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult')->willReturn($expectedToReceive);

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with('order.check_donation', [
                'TOK000000000123WEB',
                '75',
                '1',
            ])
            ->willReturn($jsonResponse);

        $donationHandler = new DonationHandler(
            $jsonRpc,
            $marketNameConverter,
            $this->mockMoneyWrapper()
        );

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $this->assertEquals(
            $expectedToReceive,
            $donationHandler->checkDonation($market, '75', '1')
        );
    }

    public function getCheckDonationProvider(): array
    {
        return [
            [false, '0'],
            [true, '50'],
        ];
    }

    /** @dataProvider getMakeDonationProvider */
    public function testMakeDonation(bool $hasError): void
    {
        $base = $this->mockCrypto();
        $base->method('getSymbol')->willReturn('BTC');

        $quote = $this->mockToken();
        $quote->method('getSymbol')->willReturn('TOK000000000123');

        $market = new Market($base, $quote);

        /** @var MarketNameConverterInterface|MockObject $marketNameConverter */
        $marketNameConverter = $this->createMock(MarketNameConverterInterface::class);
        $marketNameConverter
            ->method('convert')
            ->with($market)
            ->willReturn('TOK000000000123BTC');

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult');

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with('order.make_donation', [
                'TOK000000000123BTC',
                '30000',
                '1',
                '20000',
            ])
            ->willReturn($jsonResponse);

        $donationHandler = new DonationHandler(
            $jsonRpc,
            $marketNameConverter,
            $this->mockMoneyWrapper()
        );

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $donationHandler->makeDonation($market, '30000', '1', '20000');
        $this->assertTrue(true);
    }

    public function getMakeDonationProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }

    /** @return Crypto|MockObject */
    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    /** @return Token|MockObject */
    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }
}
