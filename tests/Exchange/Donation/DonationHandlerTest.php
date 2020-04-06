<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Donation;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Donation\DonationFetcherInterface;
use App\Exchange\Donation\DonationHandler;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Tests\MockMoneyWrapper;
use App\Utils\Converter\MarketNameConverterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DonationHandlerTest extends TestCase
{

    use MockMoneyWrapper;

    public function testCheckDonation(): void
    {
        $base = $this->mockCrypto();
        $base->method('getSymbol')->willReturn(Token::WEB_SYMBOL);

        $quote = $this->mockToken();
        $quote->method('getSymbol')->willReturn('TOK000000000123');

        $market = new Market($base, $quote);

        /** @var MarketNameConverterInterface|MockObject $marketNameConverter */
        $marketNameConverter = $this->createMock(MarketNameConverterInterface::class);
        $marketNameConverter
            ->method('convert')
            ->with($market)
            ->willReturn('TOK000000000123WEB');

        $fetcher = $this->createMock(DonationFetcherInterface::class);
        $fetcher
            ->method('checkDonation')
            ->with('TOK000000000123WEB', '75', '1')
            ->willReturn('5');

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $donationHandler = new DonationHandler(
            $fetcher,
            $marketNameConverter,
            $this->mockMoneyWrapper(),
            $this->mockCryptoRatesFetcher(),
            $this->mockCryptoManager($base),
            $bh
        );

        $this->assertEquals(
            '5',
            $donationHandler->checkDonation($market, '75', '1')
        );
    }

    public function testMakeDonation(): void
    {
        $webCrypto = $this->mockCrypto();
        $webCrypto->method('getSymbol')->willReturn(Token::WEB_SYMBOL);

        $base = $this->mockCrypto();
        $base->method('getSymbol')->willReturn(Token::BTC_SYMBOL);

        $quote = $this->mockToken();
        $quote->method('getSymbol')->willReturn('TOK000000000123');

        $market = new Market($base, $quote);

        /** @var MarketNameConverterInterface|MockObject $marketNameConverter */
        $marketNameConverter = $this->createMock(MarketNameConverterInterface::class);
        $marketNameConverter
            ->method('convert')
            ->with($market)
            ->willReturn('TOK000000000123BTC');

        $fetcher = $this->createMock(DonationFetcherInterface::class);
        $fetcher
            ->method('makeDonation')
            ->with('TOK000000000123BTC', '375000000000', '1', '20000');

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->expects($this->once())->method('withdraw');
        $bh->expects($this->once())->method('deposit');
        /** @var User|MockObject $em */
        $donorUser = $this->createMock(User::class);

        $cryptoManager = $this->mockCryptoManager($webCrypto);
        $cryptoManager
            ->expects($this->once())
            ->method('findAllIndexed')
            ->with('symbol')
            ->willReturn([
                Token::WEB_SYMBOL => $this->mockCrypto(),
                Token::BTC_SYMBOL => $this->mockCrypto(),
            ]);

        $donationHandler = new DonationHandler(
            $fetcher,
            $marketNameConverter,
            $this->mockMoneyWrapper(),
            $this->mockCryptoRatesFetcher(),
            $cryptoManager,
            $bh
        );

        $donationHandler->makeDonation($market, '30000', '1', '20000', $donorUser);
        $this->assertTrue(true);
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

    /** @return CryptoRatesFetcherInterface|MockObject */
    private function mockCryptoRatesFetcher(): CryptoRatesFetcherInterface
    {
        $crf = $this->createMock(CryptoRatesFetcherInterface::class);

        $crf->method('fetch')->willReturn([
            Token::WEB_SYMBOL => [
                Token::BTC_SYMBOL => 0.00000008,
            ],
        ]);

        return $crf;
    }

    /** @return CryptoManagerInterface|MockObject */
    private function mockCryptoManager(?Crypto $crypto): CryptoManagerInterface
    {
        $manager = $this->createMock(CryptoManagerInterface::class);

        $manager
            ->method('findBySymbol')
            ->willReturnCallback(function (string $symbol) use ($crypto) {
                return $crypto->getSymbol() == $symbol
                    ? $crypto
                    : null;
            });

        return $manager;
    }
}
