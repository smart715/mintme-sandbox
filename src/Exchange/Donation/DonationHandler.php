<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;

class DonationHandler implements DonationHandlerInterface
{
    /** @var DonationFetcherInterface */
    private $donationFetcher;

    /** @var MarketNameConverterInterface */
    private $marketNameConverter;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var CryptoRatesFetcherInterface */
    private $cryptoRatesFetcher;

    /** @var CryptoManagerInterface */
    protected $cryptoManager;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var array<int|float> */
    private $donationParams;

    public function __construct(
        DonationFetcherInterface $donationFetcher,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        CryptoManagerInterface $cryptoManager,
        BalanceHandlerInterface $balanceHandler,
        array $donationParams
    ) {
        $this->donationFetcher = $donationFetcher;
        $this->marketNameConverter = $marketNameConverter;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->cryptoManager = $cryptoManager;
        $this->balanceHandler = $balanceHandler;
        $this->donationParams = $donationParams;
    }

    public function checkDonation(Market $market, string $currency, string $amount, User $donorUser): string
    {
        $amountObj = $this->moneyWrapper->parse($amount, $currency);
        /** @var Token $token */
        $token = $market->getQuote();

        $this->checkAmount($donorUser, $amountObj, $currency);

        if (Token::BTC_SYMBOL === $currency) {
            $amountObj = $this->getBtcWorthInMintme($amountObj);
        }

        $expectedData = $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($amountObj),
            $this->getFee(),
            $token->getProfile()->getUser()->getId()
        );

        return $expectedData[0] ?? '0';
    }

    public function makeDonation(
        Market $market,
        string $currency,
        string $amount,
        string $expectedAmount,
        User $donorUser
    ): void {
        $amountObj = $this->moneyWrapper->parse($amount, $currency);
        $this->checkAmount($donorUser, $amountObj, $currency);

        /** @var Token $token */
        $token = $market->getQuote();
        /** @var User tokenCreator */
        $tokenCreator = $token->getProfile()->getUser();

        $cryptos = $this->cryptoManager->findAllIndexed('symbol');
        $this->balanceHandler->update(
            $donorUser,
            Token::getFromCrypto($cryptos[$currency]),
            $amountObj->negative(),
            'donation'
        );
        $amountToDonate = $amountObj->subtract($this->calculateFee($amountObj));
        $this->balanceHandler->update(
            $tokenCreator,
            Token::getFromCrypto($cryptos[$currency]),
            $amountToDonate,
            'donation'
        );

        $expectedAmount = $this->moneyWrapper->parse($expectedAmount, Token::WEB_SYMBOL);
        $zeroValue = new Money(0, new Currency(Token::WEB_SYMBOL));

        if ($expectedAmount->greaterThan($zeroValue)) {
            if (Token::BTC_SYMBOL === $currency) {
                $amountObj = $this->getBtcWorthInMintme($amountObj);
            }

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($amountObj),
                $this->getFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );
        }

        $this->balanceHandler->updateUserTokenRelation($donorUser, $token);
    }

    private function getBtcWorthInMintme(Money $amount): Money
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        return $this->moneyWrapper->convert(
            $amount,
            new Currency(Token::WEB_SYMBOL),
            new FixedExchange([
                Token::BTC_SYMBOL => [
                    Token::WEB_SYMBOL => 1 / $rates[Token::WEB_SYMBOL][Token::BTC_SYMBOL],
                ],
            ])
        );
    }

    public function getSellOrdersWorth(Money $sellOrdersWorth, string $currency): string
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        if (Token::BTC_SYMBOL === $currency) {
            $sellOrdersWorth = $this->moneyWrapper->convert(
                $sellOrdersWorth,
                new Currency(Token::BTC_SYMBOL),
                new FixedExchange([
                    Token::WEB_SYMBOL => [
                        Token::BTC_SYMBOL => $rates[Token::WEB_SYMBOL][Token::BTC_SYMBOL],
                    ],
                ])
            );
        }

        return $this->moneyWrapper->format($sellOrdersWorth);
    }

    private function calculateFee(Money $amount): Money
    {
        return $amount->multiply($this->getFee());
    }

    private function getFee(): string
    {
        $fee = $this->donationParams['fee'] / 100;

        return (string)$fee;
    }

    private function checkAmount(User $user, Money $amount, string $currency): void
    {
        $balance = $this->balanceHandler->balance(
            $user,
            Token::getFromSymbol($currency)
        )->getAvailable();

        if (Token::BTC_SYMBOL === $currency) {
            $minBtcAmount = $this->moneyWrapper->parse((string)$this->donationParams['minBtcAmount'], $currency);

            if ($amount->lessThan($minBtcAmount) || $amount->greaterThan($balance)) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } else {
            $minWebAmount = $this->moneyWrapper->parse((string)$this->donationParams['minWebAmount'], $currency);

            if ($amount->lessThan($minWebAmount) || $amount->greaterThan($balance)) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        }
    }
}
