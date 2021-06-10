<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Donation;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\Donation\DonationFetcherInterface;
use App\Exchange\Donation\DonationHandler;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Tests\MockMoneyWrapper;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DonationHandlerTest extends TestCase
{

    use MockMoneyWrapper;

    /** @var array<string, array<string, float|int>> */
    private $donationParams = [
        'donation' => [
            'fee' => 1,
            'minBtcAmount' => 0.000001,
            'minMintmeAmount' => 0.0001,
        ],
    ];

    public function testCheckDonation(): void
    {
        $base = $this->mockCrypto();
        $base->method('getSymbol')->willReturn(Symbols::WEB);

        /** @var User|MockObject $ownerUser */
        $ownerUser = $this->createMock(User::class);
        $ownerUser->method('getId')->willReturn(2);
        /** @var User|MockObject $donorUser */
        $donorUser = $this->createMock(User::class);
        $donorUser->method('getId')->willReturn(5);
        /** @var Profile|MockObject $profile */
        $profile = $this->createMock(User::class);
        $profile->method('getUser')->willReturn($ownerUser);

        $quote = $this->mockToken();
        $quote->method('getSymbol')->willReturn('TOK000000000123');
        $quote->method('getProfile')->willReturn($profile);

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
            ->with('TOK000000000123WEB', '75', '1', 2)
            ->willReturn('5');

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $moneyWrapper = $this->mockMoneyWrapper();
        $donationConfig = new QuickTradeConfig($this->donationParams, $moneyWrapper);

        $donationHandler = new DonationHandler(
            $fetcher,
            $marketNameConverter,
            $moneyWrapper,
            $this->mockCryptoRatesFetcher(),
            $this->mockCryptoManager($base),
            $bh,
            $donationConfig,
            $em
        );

        $this->assertEquals(
            '5',
            $donationHandler->checkDonation($market, Symbols::WEB, '75', $donorUser)
        );
    }

    public function testMakeDonation(): void
    {
        $webCrypto = $this->mockCrypto();
        $webCrypto->method('getSymbol')->willReturn(Symbols::WEB);

        $base = $this->mockCrypto();
        $base->method('getSymbol')->willReturn(Symbols::WEB);

        /** @var User|MockObject $ownerUser */
        $ownerUser = $this->createMock(User::class);
        $ownerUser->method('getId')->willReturn(2);
        /** @var Profile|MockObject $profile */
        $profile = $this->createMock(User::class);
        $profile->method('getUser')->willReturn($ownerUser);

        $quote = $this->mockToken();
        $quote->method('getSymbol')->willReturn('TOK000000000567');
        $quote->method('getProfile')->willReturn($profile);

        $market = new Market($base, $quote);

        /** @var MarketNameConverterInterface|MockObject $marketNameConverter */
        $marketNameConverter = $this->createMock(MarketNameConverterInterface::class);
        $marketNameConverter
            ->method('convert')
            ->with($market)
            ->willReturn('TOK000000000567WEB');

        $fetcher = $this->createMock(DonationFetcherInterface::class);
        $fetcher
            ->method('makeDonation')
            ->with('TOK000000000567WEB', '375000000000', '1', '20000', 3, '0');

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->expects($this->once())->method('withdraw');
        $bh->expects($this->once())->method('deposit');
        /** @var User|MockObject $donorUser */
        $donorUser = $this->createMock(User::class);

        $cryptoManager = $this->mockCryptoManager($webCrypto);
        $cryptoManager
            ->expects($this->once())
            ->method('findAllIndexed')
            ->with('symbol')
            ->willReturn([
                Symbols::WEB => $this->mockCrypto(),
                Symbols::BTC => $this->mockCrypto(),
            ]);

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $moneyWrapper = $this->mockMoneyWrapper();
        $donationConfig = new QuickTradeConfig($this->donationParams, $moneyWrapper);

        $donationHandler = new DonationHandler(
            $fetcher,
            $marketNameConverter,
            $moneyWrapper,
            $this->mockCryptoRatesFetcher(),
            $cryptoManager,
            $bh,
            $donationConfig,
            $em
        );

        $donationHandler->makeDonation($market, Symbols::BTC, '30000', '20000', $donorUser, '0');
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
            Symbols::WEB => [
                Symbols::BTC => 0.00000008,
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
