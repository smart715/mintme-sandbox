<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\CryptoRatesFetcherInterface;
use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
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
    private const CHECK_DONATION_METHOD = 'order.check_donation';
    private const MAKE_DONATION_METHOD = 'order.make_donation';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var MarketNameConverterInterface */
    private $marketNameConverter;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var CryptoRatesFetcherInterface */
    private $cryptoRatesFetcher;

    /** @var CryptoManagerInterface */
    protected $cryptoManager;

    public function __construct(
        JsonRpcInterface $jsonRpc,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->marketNameConverter = $marketNameConverter;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->cryptoManager = $cryptoManager;
    }

    public function checkDonation(Market $market, string $amount, string $fee): string
    {
        $amountObj = $this->moneyWrapper->parse($amount, $this->getSymbol($market->getBase()));
        $feeObj = $this->moneyWrapper->parse($fee, $this->getSymbol($market->getQuote()));

        if ($this->isBTCMarket($market)) {
            $amountObj = $this->convertAmountToWeb($amountObj);
            $this->convertMarketToWeb($market);
        }

        $response = $this->jsonRpc->send(self::CHECK_DONATION_METHOD, [
            $this->marketNameConverter->convert($market),
            $amountObj->getAmount(),
            $feeObj->getAmount(),
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return $response->getResult();
    }

    public function makeDonation(Market $market, string $amount, string $fee, string $expectedAmount): void
    {
        $amountObj = $this->moneyWrapper->parse($amount, $this->getSymbol($market->getBase()));
        $feeObj = $this->moneyWrapper->parse($fee, $this->getSymbol($market->getQuote()));

        if ($this->isBTCMarket($market)) {
            $amountObj = $this->convertAmountToWeb($amountObj);
            $this->convertMarketToWeb($market);
        }

        $response = $this->jsonRpc->send(self::MAKE_DONATION_METHOD, [
            $this->marketNameConverter->convert($market),
            $amountObj->getAmount(),
            $feeObj->getAmount(),
            $expectedAmount,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }
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

    private function convertMarketToWeb(Market $market): void
    {
        /** @var TradebleInterface $crypto */
        $crypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);
        $market->setBase($crypto);
    }

    private function getSymbol(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token
            ? MoneyWrapper::TOK_SYMBOL
            : $tradeble->getSymbol();
    }
}
