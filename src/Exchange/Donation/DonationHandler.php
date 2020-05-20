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
use App\Wallet\Money\MoneyWrapper;
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
            $amountObj = $this->convertAmountToWeb($amountObj);
        }

        $expectedData = $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($amountObj),
            $this->getFee(),
            $token->getProfile()->getUser()->getId()
        );

        $expectedMoneyToSpend = $this->moneyWrapper->parse(
            $expectedData[1] ?? '0',
            Token::WEB_SYMBOL
        );
        $this->checkAmount($donorUser, $expectedMoneyToSpend, Token::WEB_SYMBOL);

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
        /** @var Token $token */
        $token = $market->getQuote();

        $this->checkAmount($donorUser, $amountObj, $currency);

        if (Token::BTC_SYMBOL === $currency) {
            $amountInWeb = $this->convertAmountToWeb($amountObj);
            $cryptos = $this->cryptoManager->findAllIndexed('symbol');

            $this->balanceHandler->update(
                $donorUser,
                Token::getFromCrypto($cryptos[Token::BTC_SYMBOL]),
                $amountObj->negative(),
                'donation'
            );
            $this->balanceHandler->update(
                $donorUser,
                Token::getFromCrypto($cryptos[Token::WEB_SYMBOL]),
                $amountInWeb,
                'donation'
            );

            $amountObj = $amountInWeb;
        }

        $this->donationFetcher->makeDonation(
            $donorUser->getId(),
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($amountObj),
            $this->getFee(),
            $expectedAmount,
            $token->getProfile()->getUser()->getId()
        );
    }

    private function convertAmountToWeb(Money $amount): Money
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
