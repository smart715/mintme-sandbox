<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Donation;

use App\Communications\AMQP\MarketAMQPInterface;
use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\Donation\DonationCheckerInterface;
use App\Exchange\Donation\DonationFetcherInterface;
use App\Exchange\Donation\DonationHandler;
use App\Exchange\Donation\Model\CheckDonationResult;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Market\Model\SellOrdersSummaryResult;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Tests\Mocks\MockMoneyWrapper;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\CryptoCalculator;
use App\Utils\Symbols;
use App\Utils\Validator\ValidatorInterface;
use App\Utils\ValidatorFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class DonationHandlerTest extends TestCase
{

    use MockMoneyWrapper;

    private const TOKEN_OWNER_ID = 1;
    private const DONOR_ID = 2;
    private const WEB_SYMBOL = 'WEB';
    private const BTC_SYMBOL = 'BTC';
    private const TOK_SYMBOL = 'TOK000000000001';
    private const TOK_CURRENCY_SYMBOL = 'TOK';
    private const REFERRAL_FEE = 0.005;

    private array $donationParams;
    private array $minAmounts;

    protected function setUp(): void
    {
        $this->donationParams = [
            'buy_fee' => [
                'coin' => 0.002,
                'token' => 1,
            ],
        ];
        $this->minAmounts = [
            'TOK' => 10,
            'BTC' => 0.000001,
            'MINTME' => 0.0001,
        ];
    }

    public function testCheckDonationOneWayDonation(): void
    {
        $donorUser = $this->mockUser(self::DONOR_ID);

        $market = $this->mockMarket(self::WEB_SYMBOL, self::TOK_SYMBOL);

        $checkResultTokensAmount = new Money('100', new Currency(self::TOK_CURRENCY_SYMBOL));
        $checkResultTokensWorth = new Money('100', new Currency(self::WEB_SYMBOL));

        $donationHandler = new DonationHandler(
            $this->mockDonationFetcher(),
            $this->mockMarketNameConverter($market),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->createMock(QuickTradeConfig::class),
            $this->mockEntityManager(),
            $this->mockMarketHandler('0'),
            $this->createMock(ExchangerInterface::class),
            $this->createMock(MarketFactoryInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(MarketAMQPInterface::class),
            $this->createMock(UserActionLogger::class),
            $this->createMock(UserNotificationManagerInterface::class),
            $this->createMock(MailerInterface::class),
            $this->mockOneWayDonationChecker($checkResultTokensAmount, $checkResultTokensWorth),
            $this->mockCryptoCalculator(),
            self::REFERRAL_FEE
        );

        $checkTradeResult = $donationHandler->checkDonation($market, '100', $donorUser);

        $this->assertEquals(
            new Money('100', new Currency(self::TOK_CURRENCY_SYMBOL)),
            $checkTradeResult->getExpectedAmount()
        );
        $this->assertEquals(
            new Money('100', new Currency(self::WEB_SYMBOL)),
            $checkTradeResult->getWorth()
        );
    }

    public function testCheckDonationTwoWayDonation(): void
    {
        $donorUser = $this->mockUser(self::DONOR_ID);
        $market = $this->mockMarket(self::WEB_SYMBOL, self::TOK_SYMBOL);

        $checkResultTokensAmount = new Money('100', new Currency(self::TOK_CURRENCY_SYMBOL));
        $checkResultTokensWorth = new Money('100', new Currency(self::WEB_SYMBOL));

        $donationHandler = new DonationHandler(
            $this->mockDonationFetcher(),
            $this->mockMarketNameConverter($market),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->createMock(QuickTradeConfig::class),
            $this->mockEntityManager(),
            $this->mockMarketHandler('1'),
            $this->createMock(ExchangerInterface::class),
            $this->createMock(MarketFactoryInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(MarketAMQPInterface::class),
            $this->createMock(UserActionLogger::class),
            $this->createMock(UserNotificationManagerInterface::class),
            $this->createMock(MailerInterface::class),
            $this->mockTwoWayDonationChecker($checkResultTokensAmount, $checkResultTokensWorth),
            $this->mockCryptoCalculator(),
            self::REFERRAL_FEE
        );

        $checkTradeResult = $donationHandler->checkDonation($market, '100', $donorUser);

        $this->assertEquals(
            new Money('100', new Currency(self::TOK_CURRENCY_SYMBOL)),
            $checkTradeResult->getExpectedAmount()
        );
        $this->assertEquals(
            new Money('100', new Currency(self::WEB_SYMBOL)),
            $checkTradeResult->getWorth()
        );
    }

    public function testCheckDonationFullDonation(): void
    {
        $donorUser = $this->mockUser(self::DONOR_ID);
        $market = $this->mockMarket(self::WEB_SYMBOL, self::TOK_SYMBOL);

        $checkResultTokensAmount = new Money('100', new Currency(self::TOK_CURRENCY_SYMBOL));
        $checkResultTokensWorth = new Money('100', new Currency(self::WEB_SYMBOL));

        $donationHandler = new DonationHandler(
            $this->mockDonationFetcher(),
            $this->mockMarketNameConverter($market),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->createMock(QuickTradeConfig::class),
            $this->mockEntityManager(),
            $this->mockMarketHandler('0'),
            $this->createMock(ExchangerInterface::class),
            $this->createMock(MarketFactoryInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(MarketAMQPInterface::class),
            $this->createMock(UserActionLogger::class),
            $this->createMock(UserNotificationManagerInterface::class),
            $this->createMock(MailerInterface::class),
            $this->mockOneWayDonationChecker($checkResultTokensAmount, $checkResultTokensWorth),
            $this->mockCryptoCalculator(),
            self::REFERRAL_FEE
        );

        $checkTradeResult = $donationHandler->checkDonation($market, '100', $donorUser);

        $this->assertEquals(
            new Money('100', new Currency(self::TOK_CURRENCY_SYMBOL)),
            $checkTradeResult->getExpectedAmount()
        );
        $this->assertEquals(
            new Money('100', new Currency(self::WEB_SYMBOL)),
            $checkTradeResult->getWorth()
        );
    }

    public function testMakeDonation(): void
    {
        $ownerUser = $this->mockUser(self::TOKEN_OWNER_ID);
        $donorUser = $this->mockUser(self::DONOR_ID);
        $profile = $this->mockProfile($ownerUser);
        $quote = $this->mockToken(self::TOK_SYMBOL, $profile);

        $market = $this->mockMarket(self::WEB_SYMBOL, self::TOK_SYMBOL);

        $moneyWrapper = $this->mockMoneyWrapper();
        $donationConfig = new QuickTradeConfig(
            $this->donationParams,
            $this->minAmounts,
            $moneyWrapper,
        );

        $checkResultTokensAmount = new Money('20000', new Currency(self::TOK_CURRENCY_SYMBOL));
        $checkResultTokensWorth = new Money('20000', new Currency(self::WEB_SYMBOL));

        $donationHandler = new DonationHandler(
            $this->mockDonationFetcher(),
            $this->mockMarketNameConverter($market),
            $moneyWrapper,
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $donationConfig,
            $this->mockEntityManager(),
            $this->mockMarketHandler('0'),
            $this->createMock(ExchangerInterface::class),
            $this->createMock(MarketFactoryInterface::class),
            $this->mockValidatorFactory(),
            $this->createMock(MarketAMQPInterface::class),
            $this->createMock(UserActionLogger::class),
            $this->createMock(UserNotificationManagerInterface::class),
            $this->createMock(MailerInterface::class),
            $this->mockOneWayDonationChecker($checkResultTokensAmount, $checkResultTokensWorth),
            $this->mockCryptoCalculator(),
            self::REFERRAL_FEE
        );

        $donation = $donationHandler->makeDonation($market, '30000', '20000', $donorUser);

        $this->assertEquals('30000', $donation->getAmount()->getAmount());
        $this->assertEquals('20000', $donation->getTokenAmount()->getAmount());
        $this->assertEquals('15000', $donation->getFeeAmount()->getAmount());
        $this->assertEquals(self::TOKEN_OWNER_ID, $donation->getTokenCreator()->getId());
        $this->assertEquals(self::DONOR_ID, $donation->getDonor()->getId());
        $this->assertEquals($quote, $donation->getToken());
        $this->assertEquals('30000', $donation->getReceiverAmount()->getAmount());
        $this->assertEquals('15000', $donation->getReceiverFeeAmount()->getAmount());
    }
    public function testMakeDonationWithNonMintmeCrypto(): void
    {
        $market = $this->mockMarket(self::BTC_SYMBOL, self::TOK_SYMBOL);

        $moneyWrapper = $this->mockMoneyWrapper();
        $donationConfig = new QuickTradeConfig(
            $this->donationParams,
            $this->minAmounts,
            $moneyWrapper
        );

        $checkResultTokensAmount = new Money('20000', new Currency(self::TOK_CURRENCY_SYMBOL));
        $checkResultTokensWorth = new Money('20000', new Currency(self::WEB_SYMBOL));

        $donationHandler = new DonationHandler(
            $this->mockDonationFetcher(),
            $this->mockMarketNameConverter($market),
            $moneyWrapper,
            $this->mockCryptoManager(),
            $this->mockBalanceHandler('BTC'),
            $donationConfig,
            $this->mockEntityManager(),
            $this->mockMarketHandler('10000000000'),
            $this->createMock(ExchangerInterface::class),
            $this->createMock(MarketFactoryInterface::class),
            $this->mockValidatorFactory(),
            $this->createMock(MarketAMQPInterface::class),
            $this->createMock(UserActionLogger::class),
            $this->createMock(UserNotificationManagerInterface::class),
            $this->createMock(MailerInterface::class),
            $this->mockTwoWayDonationChecker($checkResultTokensAmount, $checkResultTokensWorth),
            $this->mockCryptoCalculator(),
            self::REFERRAL_FEE
        );

        $donorUser = $this->mockUser(self::DONOR_ID);
        $ownerUser = $this->mockUser(self::TOKEN_OWNER_ID);
        $profile = $this->mockProfile($ownerUser);
        $quote = $this->mockToken(self::TOK_SYMBOL, $profile);

        $donation = $donationHandler->makeDonation($market, '0', '20000', $donorUser);

        $this->assertEquals('0', $donation->getAmount()->getAmount());
        $this->assertEquals('20000', $donation->getTokenAmount()->getAmount());
        $this->assertEquals('0', $donation->getFeeAmount()->getAmount());
        $this->assertEquals(self::TOKEN_OWNER_ID, $donation->getTokenCreator()->getId());
        $this->assertEquals(self::DONOR_ID, $donation->getDonor()->getId());
        $this->assertEquals($quote, $donation->getToken());
    }

    public function testMakeDonationWithExpectedAmountLessThanMinimum(): void
    {
        $market = $this->mockMarket(self::WEB_SYMBOL, self::TOK_SYMBOL);

        $moneyWrapper = $this->mockMoneyWrapper();
        $donationConfig = new QuickTradeConfig(
            $this->donationParams,
            $this->minAmounts,
            $moneyWrapper,
        );

        $checkResultTokensAmount = new Money('2', new Currency(self::TOK_CURRENCY_SYMBOL));
        $checkResultTokensWorth = new Money('2', new Currency(self::WEB_SYMBOL));

        $donationHandler = new DonationHandler(
            $this->mockDonationFetcher(),
            $this->mockMarketNameConverter($market),
            $moneyWrapper,
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $donationConfig,
            $this->mockEntityManager(),
            $this->mockMarketHandler('1000000000000'),
            $this->createMock(ExchangerInterface::class),
            $this->createMock(MarketFactoryInterface::class),
            $this->mockValidatorFactory(),
            $this->createMock(MarketAMQPInterface::class),
            $this->createMock(UserActionLogger::class),
            $this->createMock(UserNotificationManagerInterface::class),
            $this->createMock(MailerInterface::class),
            $this->mockTwoWayDonationChecker($checkResultTokensAmount, $checkResultTokensWorth),
            $this->mockCryptoCalculator(),
            self::REFERRAL_FEE
        );

        $donorUser = $this->mockUser(self::DONOR_ID);
        $ownerUser = $this->mockUser(self::TOKEN_OWNER_ID);
        $profile = $this->mockProfile($ownerUser);
        $quote = $this->mockToken(self::TOK_SYMBOL, $profile);

        $donation = $donationHandler->makeDonation($market, '30000', '2', $donorUser);

        $this->assertEquals('30000', $donation->getAmount()->getAmount());
        $this->assertEquals('2', $donation->getTokenAmount()->getAmount());
        $this->assertEquals('15000', $donation->getFeeAmount()->getAmount());
        $this->assertEquals(self::TOKEN_OWNER_ID, $donation->getTokenCreator()->getId());
        $this->assertEquals(self::DONOR_ID, $donation->getDonor()->getId());
        $this->assertEquals($quote, $donation->getToken());
        $this->assertEquals('30000', $donation->getReceiverAmount()->getAmount());
        $this->assertEquals('15000', $donation->getReceiverFeeAmount()->getAmount());
    }

    public function testMakeDonationWithExpectedAmountLessThanMinimumOnNonMintmeCrypto(): void
    {
        $market = $this->mockMarket(self::BTC_SYMBOL, self::TOK_SYMBOL);

        $moneyWrapper = $this->mockMoneyWrapper();
        $donationConfig = new QuickTradeConfig(
            $this->donationParams,
            $this->minAmounts,
            $moneyWrapper,
        );

        $checkResultTokensAmount = new Money('2', new Currency(self::TOK_CURRENCY_SYMBOL));
        $checkResultTokensWorth = new Money('2', new Currency(self::WEB_SYMBOL));

        $donationHandler = new DonationHandler(
            $this->mockDonationFetcher(),
            $this->mockMarketNameConverter($market),
            $moneyWrapper,
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(self::BTC_SYMBOL),
            $donationConfig,
            $this->mockEntityManager(),
            $this->mockMarketHandler('10000000000'),
            $this->createMock(ExchangerInterface::class),
            $this->createMock(MarketFactoryInterface::class),
            $this->mockValidatorFactory(),
            $this->createMock(MarketAMQPInterface::class),
            $this->createMock(UserActionLogger::class),
            $this->createMock(UserNotificationManagerInterface::class),
            $this->createMock(MailerInterface::class),
            $this->mockTwoWayDonationChecker($checkResultTokensAmount, $checkResultTokensWorth),
            $this->mockCryptoCalculator(),
            self::REFERRAL_FEE
        );

        $donorUser = $this->mockUser(self::DONOR_ID);
        $ownerUser = $this->mockUser(self::TOKEN_OWNER_ID);
        $profile = $this->mockProfile($ownerUser);
        $quote = $this->mockToken('TOK000000000567', $profile);

        $donation = $donationHandler->makeDonation($market, '0', '2', $donorUser);

        $this->assertEquals('0', $donation->getAmount()->getAmount());
        $this->assertEquals('2', $donation->getTokenAmount()->getAmount());
        $this->assertEquals('0', $donation->getFeeAmount()->getAmount());
        $this->assertEquals(self::TOKEN_OWNER_ID, $donation->getTokenCreator()->getId());
        $this->assertEquals(self::DONOR_ID, $donation->getDonor()->getId());
        $this->assertEquals($quote, $donation->getToken());
    }

    private function mockCrypto(string $symbol = "WEB"): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($symbol);
        $crypto->method('getMoneySymbol')->willReturn($symbol);

        return $crypto;
    }

    private function mockToken(string $symbol, Profile $profile): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getSymbol')->willReturn($symbol);
        $token->method('getProfile')->willReturn($profile);

        return $token;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);

        $cryptoManager
            ->method('findBySymbol')
            ->willReturnCallback(function (string $symbol): ?Crypto {
                return self::WEB_SYMBOL === $symbol || self::BTC_SYMBOL === $symbol
                    ? $this->mockCrypto($symbol)
                    : null;
            });

        $cryptoManager
            ->method('findAllIndexed')
            ->with('symbol')
            ->willReturn([
                Symbols::WEB => $this->mockCrypto(),
                Symbols::BTC => $this->mockCrypto(),
            ]);

        return $cryptoManager;
    }

    private function mockValidatorFactory(): ValidatorFactoryInterface
    {
        $validatorFactory = $this->createMock(ValidatorFactoryInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(true);
        $validatorFactory
            ->method('createMinUsdValidator')
            ->willReturn($validator);
        $validatorFactory
            ->method('createMinTradableValidator')
            ->willReturn($validator);

        return $validatorFactory;
    }

    private function mockMarketHandler(string $sellOrdersSummaryBaseAmount): MarketHandlerInterface
    {
        $sosr = $this->createMock(SellOrdersSummaryResult::class);
        $sosr->method('getBaseAmount')->willReturn($sellOrdersSummaryBaseAmount);

        $marketHandler = $this->createMock(MarketHandlerInterface::class);
        $marketHandler->method('getSellOrdersSummary')->willReturn($sosr);

        return $marketHandler;
    }

    private function mockUser(int $id = 1): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function mockMarketNameConverter(Market $market): MarketNameConverterInterface
    {
        $marketNameConverter = $this->createMock(MarketNameConverterInterface::class);
        $marketNameConverter
            ->method('convert')
            ->with($market)
            ->willReturn(self::TOK_SYMBOL);

        return $marketNameConverter;
    }

    private function mockDonationFetcher(): DonationFetcherInterface
    {
        $fetcher = $this->createMock(DonationFetcherInterface::class);
        $fetcher
            ->method('makeDonation')
            ->with(self::DONOR_ID, self::TOK_SYMBOL, '20000', '1', '20000', self::TOKEN_OWNER_ID);

        return $fetcher;
    }

    private function mockBalanceResult(string $symbol): BalanceResult
    {
        $balanceResult = $this->createMock(BalanceResult::class);
        $balanceResult->method('getAvailable')
            ->willReturn(new Money('1000000000000', new Currency($symbol)));

        return $balanceResult;
    }

    private function mockBalanceHandler(string $symbol = 'WEB'): BalanceHandlerInterface
    {
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->method('balance')->willReturn($this->mockBalanceResult($symbol));

        return $bh;
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    private function mockProfile(?User $user = null): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')->willReturn($user);

        return $profile;
    }

    private function mockMarket(string $base, string $quote): Market
    {
        $tokenOwner = $this->mockUser(self::TOKEN_OWNER_ID);
        $profile = $this->mockProfile($tokenOwner);

        return new Market($this->mockCrypto($base), $this->mockToken($quote, $profile));
    }

    private function mockOneWayDonationChecker(
        Money $expectedTokensAmount,
        Money $expectedTokensWorth
    ): DonationCheckerInterface {
        $donationChecker = $this->createMock(DonationCheckerInterface::class);
        $donationChecker
            ->method('checkOneWayDonation')
            ->willReturn(new CheckDonationResult($expectedTokensAmount, $expectedTokensWorth));
        $donationChecker
            ->expects($this->never())
            ->method('checkTwoWayDonation');

        return $donationChecker;
    }

    private function mockTwoWayDonationChecker(
        Money $expectedTokensAmount,
        Money $expectedTokensWorth
    ): DonationCheckerInterface {
        $donationChecker = $this->createMock(DonationCheckerInterface::class);
        $donationChecker
            ->method('checkTwoWayDonation')
            ->willReturn(new CheckDonationResult($expectedTokensAmount, $expectedTokensWorth));
        $donationChecker
            ->expects($this->never())
            ->method('checkOneWayDonation');

        return $donationChecker;
    }


    private function mockCryptoCalculator(
        string $returnWebAmount = '0'
    ): CryptoCalculator {
        $cryptoCalculator = $this->createMock(CryptoCalculator::class);
        $cryptoCalculator
            ->method('getMintmeWorth')
            ->willReturn(new Money($returnWebAmount, new Currency(Symbols::WEB)));

        return $cryptoCalculator;
    }
}
