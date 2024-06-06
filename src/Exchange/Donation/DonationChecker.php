<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Entity\User;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\Donation\Model\CheckDonationResult;
use App\Exchange\Market;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

class DonationChecker implements DonationCheckerInterface
{
    private DonationFetcherInterface $donationFetcher;
    private MarketNameConverterInterface $marketNameConverter;
    private MoneyWrapperInterface $moneyWrapper;
    private QuickTradeConfig $quickTradeConfig;

    public function __construct(
        DonationFetcherInterface $donationFetcher,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper,
        QuickTradeConfig $quickTradeConfig
    ) {
        $this->donationFetcher = $donationFetcher;
        $this->marketNameConverter = $marketNameConverter;
        $this->moneyWrapper = $moneyWrapper;
        $this->quickTradeConfig = $quickTradeConfig;
    }

    public function checkOneWayDonation(Market $market, Money $donationAmount, User $tokenCreator): CheckDonationResult
    {
        $checkDonationRawResult = $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($donationAmount),
            $this->quickTradeConfig->getBuyTokenFee(),
            $tokenCreator->getId()
        );

        $expectedTokensAmount = $this->moneyWrapper->parse(
            $checkDonationRawResult->getExpectedTokens(),
            Symbols::TOK
        );

        $expectedTokensWorth = $this->moneyWrapper->parse(
            $checkDonationRawResult->getTokensWorth(),
            $market->getBase()->getSymbol()
        );

        return new CheckDonationResult($expectedTokensAmount, $expectedTokensWorth);
    }

    //viabtc contains rounding bug\feature
    //for twoWayDonation we sometimes have "wrong expected tokens amount" error
    //with feeRates in [0.001, 0.003, 0.005, 0.007, 0.009]
    public function checkTwoWayDonation(Market $market, Money $donationAmount, User $tokenCreator): CheckDonationResult
    {
        $checkDonationResultFirst = $this->checkOneWayDonation($market, $donationAmount, $tokenCreator);
        $expectedTokensWorth = $checkDonationResultFirst->getExpectedTokensWorth();

        $checkDonationResultSecond = $this->checkOneWayDonation($market, $expectedTokensWorth, $tokenCreator);
        $expectedTokensAmount = $checkDonationResultSecond->getExpectedTokensAmount();

        return new CheckDonationResult($expectedTokensAmount, $expectedTokensWorth);
    }
}
