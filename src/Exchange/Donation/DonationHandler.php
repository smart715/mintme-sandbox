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
    }

    public function checkDonation(Market $market, string $currency, string $amount): array
    {
        $amountObj = $this->moneyWrapper->parse($amount, $currency);
        $feeObj = $this->moneyWrapper->parse(
            (string)$this->donationParams['fee'],
            Token::WEB_SYMBOL
        );
        /** @var Token $token */
        $token = $market->getQuote();
        /** @var User $user */
        $user = $token->getProfile()->getUser();

        $this->checkAmount($user, $amountObj, $currency);

        if (Token::BTC_SYMBOL === $currency) {
            $amountObj = $this->convertAmountToWeb($amountObj);
        }

        return $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $amountObj->getAmount(),
            $feeObj->getAmount(),
            $user->getId()
        );
    }

    public function makeDonation(
        Market $market,
        string $currency,
        string $amount,
        string $expectedAmount,
        User $donorUser
    ): void {
        $amountObj = $this->moneyWrapper->parse($amount, $currency);
        $feeObj = $this->moneyWrapper->parse(
            (string)$this->donationParams['fee'],
            Token::WEB_SYMBOL
        );
        /** @var Token $token */
        $token = $market->getQuote();
        /** @var User $user */
        $user = $token->getProfile()->getUser();

        $this->checkAmount($user, $amountObj, $currency);

        if (Token::BTC_SYMBOL === $currency) {
            $amountInWeb = $this->convertAmountToWeb($amountObj);
            $cryptos = $this->cryptoManager->findAllIndexed('symbol');

            $this->balanceHandler->withdraw(
                $donorUser,
                Token::getFromCrypto($cryptos[Token::BTC_SYMBOL]),
                $amountObj
            );
            $this->balanceHandler->deposit(
                $donorUser,
                Token::getFromCrypto($cryptos[Token::WEB_SYMBOL]),
                $amountInWeb
            );

            $amountObj = $amountInWeb;
        }

        $this->donationFetcher->makeDonation(
            $this->marketNameConverter->convert($market),
            $amountObj->getAmount(),
            $feeObj->getAmount(),
            $expectedAmount,
            $user->getId()
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
