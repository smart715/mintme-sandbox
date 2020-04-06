<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
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

    public function __construct(
        DonationFetcherInterface $donationFetcher,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        CryptoManagerInterface $cryptoManager,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->donationFetcher = $donationFetcher;
        $this->marketNameConverter = $marketNameConverter;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->cryptoManager = $cryptoManager;
        $this->balanceHandler = $balanceHandler;
    }

    public function checkDonation(Market $market, string $amount, string $fee): string
    {
        $amountObj = $this->moneyWrapper->parse($amount, $this->getSymbol($market->getBase()));
        $feeObj = $this->moneyWrapper->parse($fee, $this->getSymbol($market->getQuote()));

        if ($this->isBTCMarket($market)) {
            $amountObj = $this->convertAmountToWeb($amountObj);
        }

        return $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $amountObj->getAmount(),
            $feeObj->getAmount()
        );
    }

    public function makeDonation(
        Market $market,
        string $amount,
        string $fee,
        string $expectedAmount,
        User $donorUser
    ): void {
        $amountObj = $this->moneyWrapper->parse($amount, $this->getSymbol($market->getBase()));
        $feeObj = $this->moneyWrapper->parse($fee, $this->getSymbol($market->getQuote()));

        if ($this->isBTCMarket($market)) {
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
            $expectedAmount
        );
    }

    private function isBTCMarket(Market $market): bool
    {
        return Token::BTC_SYMBOL === $market->getBase()->getSymbol();
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

    private function getSymbol(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token
            ? MoneyWrapper::TOK_SYMBOL
            : $tradeble->getSymbol();
    }
}
