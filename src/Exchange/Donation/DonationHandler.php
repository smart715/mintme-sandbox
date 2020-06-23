<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\DonationConfig;
use App\Exchange\Donation\Model\CheckDonationResult;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
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

    /** @var DonationConfig */
    private $donationConfig;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        DonationFetcherInterface $donationFetcher,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        CryptoManagerInterface $cryptoManager,
        BalanceHandlerInterface $balanceHandler,
        DonationConfig $donationConfig,
        EntityManagerInterface $em
    ) {
        $this->donationFetcher = $donationFetcher;
        $this->marketNameConverter = $marketNameConverter;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->cryptoManager = $cryptoManager;
        $this->balanceHandler = $balanceHandler;
        $this->donationConfig = $donationConfig;
        $this->em = $em;
    }

    public function checkDonation(
        Market $market,
        string $currency,
        string $amount,
        User $donorUser
    ): CheckDonationResult {
        $amountObj = $this->moneyWrapper->parse($amount, $currency);
        /** @var Token $token */
        $token = $market->getQuote();

        $this->checkAmount($donorUser, $amountObj, $currency);

        if (Token::BTC_SYMBOL === $currency) {
            $amountObj = $this->getBtcWorthInMintme($amountObj);
        }

        return $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($amountObj),
            $this->donationConfig->getFee(),
            $token->getProfile()->getUser()->getId()
        );
    }

    public function makeDonation(
        Market $market,
        string $currency,
        string $amount,
        string $expectedAmount,
        User $donorUser,
        Money $sellOrdersSummary
    ): void {
        $amountObj = $this->moneyWrapper->parse($amount, $currency);
        $this->checkAmount($donorUser, $amountObj, $currency);
        /** @var Token $token */
        $token = $market->getQuote();
        /** @var User tokenCreator */
        $tokenCreator = $token->getProfile()->getUser();

        $expectedAmount = $this->moneyWrapper->parse($expectedAmount, Token::WEB_SYMBOL);
        $minTokensAmount = $this->donationConfig->getMinTokensAmount();
        $donationAmount = $amountObj;

        if (Token::BTC_SYMBOL === $currency) {
            $donationAmount = $this->getBtcWorthInMintme($donationAmount);
        }

        $checkDonationResult = $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($donationAmount),
            $this->donationConfig->getFee(),
            $token->getProfile()->getUser()->getId()
        );

        $currentExpectedAmount = $this->moneyWrapper->parse(
            $checkDonationResult->getExpectedTokens(),
            Token::WEB_SYMBOL
        );
        $tokensWorth = $this->moneyWrapper->parse($checkDonationResult->getTokensWorth(), Token::WEB_SYMBOL);

        // Check expected tokens amount.
        if ($currentExpectedAmount->lessThan($expectedAmount)) {
            throw new ApiBadRequestException('Tokens availability changed. Please adjust donation amount.');
        }

        $twoWayDonation = $expectedAmount->greaterThan($minTokensAmount)
            && $expectedAmount->isPositive() && $sellOrdersSummary->lessThan($donationAmount);

        if ($expectedAmount->greaterThan($minTokensAmount) && $sellOrdersSummary->greaterThanOrEqual($donationAmount)) {
            // Donate using donation viabtc API (token creator has available sell orders)
            $feeAmount = new Money(0, new Currency($currency));

            if (Token::BTC_SYMBOL === $currency) {
                $this->sendAmountFromUserToUser(
                    $donorUser,
                    $amountObj,
                    $donorUser,
                    $donationAmount,
                    Token::BTC_SYMBOL,
                    Token::WEB_SYMBOL
                );
            }

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($donationAmount),
                $this->donationConfig->getFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );
            $this->saveDonation($donorUser, $tokenCreator, $currency, $amountObj, $feeAmount, $expectedAmount);
        } elseif (Token::BTC_SYMBOL === $currency && $twoWayDonation) {
            // Donate BTC using donation viabtc API AND donation from user to user.
            $tokensWorthInBtc = $this->getMintmeWorthInBtc($tokensWorth);
            $this->sendAmountFromUserToUser(
                $donorUser,
                $tokensWorthInBtc,
                $donorUser,
                $tokensWorth,
                Token::BTC_SYMBOL,
                Token::WEB_SYMBOL
            );
            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorth),
                $this->donationConfig->getFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );

            $donationAmountLeftInBtc = $amountObj->subtract($tokensWorthInBtc);
            $feeAmount = $this->calculateFee($donationAmountLeftInBtc);
            $amountToDonate = $donationAmountLeftInBtc->subtract($feeAmount);
            $this->sendAmountFromUserToUser(
                $donorUser,
                $donationAmountLeftInBtc,
                $donorUser,
                $amountToDonate,
                Token::BTC_SYMBOL,
                Token::WEB_SYMBOL
            );
            $this->saveDonation($donorUser, $tokenCreator, $currency, $amountToDonate, $feeAmount, $expectedAmount);
        } elseif (Token::WEB_SYMBOL === $currency && $twoWayDonation) {
            // Donate MINTME using donation viabtc API AND donation from user to user.
            $amountToSendManually = $donationAmount->subtract($tokensWorth);
            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorth),
                $this->donationConfig->getFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );
            $feeAmount = $this->calculateFee($amountToSendManually);
            $amountToDonate = $amountToSendManually->subtract($feeAmount);
            $this->sendAmountFromUserToUser(
                $donorUser,
                $amountToSendManually,
                $tokenCreator,
                $amountToDonate,
                $currency,
                $currency
            );
            $this->saveDonation($donorUser, $tokenCreator, $currency, $amountToDonate, $feeAmount, $expectedAmount);
        } else {
            // Donate (send) funds from user to user (token creator has no sell orders).
            $feeAmount = $this->calculateFee($amountObj);
            $amountToDonate = $amountObj->subtract($feeAmount);
            $this->sendAmountFromUserToUser(
                $donorUser,
                $amountObj,
                $tokenCreator,
                $amountToDonate,
                $currency,
                $currency
            );
            $this->saveDonation($donorUser, $tokenCreator, $currency, $amountToDonate, $feeAmount, $expectedAmount);
        }

        $this->balanceHandler->updateUserTokenRelation($donorUser, $token);
    }

    public function getTokensWorth(string $tokensWorth, string $currency): string
    {
        if (Token::BTC_SYMBOL === $currency) {
            $tokensWorth = $this->moneyWrapper->parse($tokensWorth, Token::WEB_SYMBOL);
            $tokensWorthInBtc = $this->getMintmeWorthInBtc($tokensWorth);

            return $this->moneyWrapper->format($tokensWorthInBtc);
        }

        return $tokensWorth;
    }

    public function saveDonation(
        User $donor,
        User $tokenCreator,
        string $currency,
        Money $amount,
        Money $feeAmount,
        Money $tokenAmount
    ): void {
        $donation = new Donation();
        $donation
            ->setDonor($donor)
            ->setTokenCreator($tokenCreator)
            ->setCurrency($currency)
            ->setAmount($amount)
            ->setFeeAmount($feeAmount)
            ->setTokenAmount($tokenAmount);

        $this->em->persist($donation);
        $this->em->flush();
    }

    private function sendAmountFromUserToUser(
        User $withdrawFromUser,
        Money $donationAmount,
        User $depositToUser,
        Money $amountToDonate,
        string $withdrawCurrency,
        string $depositCurrency
    ): void {
        $cryptos = $this->cryptoManager->findAllIndexed('symbol');
        $this->balanceHandler->update(
            $withdrawFromUser,
            Token::getFromCrypto($cryptos[$withdrawCurrency]),
            $donationAmount->negative(),
            'donation'
        );
        $this->balanceHandler->update(
            $depositToUser,
            Token::getFromCrypto($cryptos[$depositCurrency]),
            $amountToDonate,
            'donation'
        );
    }

    private function getMintmeWorthInBtc(Money $amountInMintme): Money
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        return $this->moneyWrapper->convert(
            $amountInMintme,
            new Currency(Token::BTC_SYMBOL),
            new FixedExchange($rates)
        );
    }

    private function getBtcWorthInMintme(Money $amountInBtc): Money
    {
        $rates = $this->cryptoRatesFetcher->fetch();
        $rates[Token::BTC_SYMBOL][Token::WEB_SYMBOL] = 1 / $rates[Token::WEB_SYMBOL][Token::BTC_SYMBOL];

        return $this->moneyWrapper->convert(
            $amountInBtc,
            new Currency(Token::WEB_SYMBOL),
            new FixedExchange($rates)
        );
    }

    private function calculateFee(Money $amount): Money
    {
        return $amount->multiply($this->donationConfig->getFee());
    }

    private function checkAmount(User $user, Money $amount, string $currency): void
    {
        $balance = $this->balanceHandler->balance(
            $user,
            Token::getFromSymbol($currency)
        )->getAvailable();

        if (Token::BTC_SYMBOL === $currency) {
            $minBtcAmount = $this->donationConfig->getMinBtcAmount();

            if ($amount->lessThan($minBtcAmount) || $amount->greaterThan($balance)) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } else {
            $minMintmeAmount = $this->donationConfig->getMinMintmeAmount();

            if ($amount->lessThan($minMintmeAmount) || $amount->greaterThan($balance)) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        }
    }
}
