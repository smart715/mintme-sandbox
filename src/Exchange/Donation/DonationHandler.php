<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
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

    /** @var array<int|float> */
    private $donationParams;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        DonationFetcherInterface $donationFetcher,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        CryptoManagerInterface $cryptoManager,
        BalanceHandlerInterface $balanceHandler,
        array $donationParams,
        EntityManagerInterface $em
    ) {
        $this->donationFetcher = $donationFetcher;
        $this->marketNameConverter = $marketNameConverter;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->cryptoManager = $cryptoManager;
        $this->balanceHandler = $balanceHandler;
        $this->donationParams = $donationParams;
        $this->em = $em;
    }

    public function checkDonation(Market $market, string $currency, string $amount, User $donorUser): array
    {
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
            $this->getFee(),
            $token->getProfile()->getUser()->getId()
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
        $this->checkAmount($donorUser, $amountObj, $currency);
        /** @var Token $token */
        $token = $market->getQuote();
        /** @var User tokenCreator */
        $tokenCreator = $token->getProfile()->getUser();

        $expectedAmount = $this->moneyWrapper->parse($expectedAmount, Token::WEB_SYMBOL);
        $minTokensAmount = $this->moneyWrapper->parse(
            (string)$this->donationParams['minTokensAmount'],
            Token::WEB_SYMBOL
        );

        $currentExpectedData = $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($amountObj),
            $this->getFee(),
            $token->getProfile()->getUser()->getId()
        );

        $currentExpectedAmount = $this->moneyWrapper->parse($currentExpectedData[0] ?? '0', Token::WEB_SYMBOL);
        $tokensWorth = $this->moneyWrapper->parse($currentExpectedData[1] ?? '0', Token::WEB_SYMBOL);
        $donationAmount = $amountObj;

        // Check expected tokens amount.
        if ($currentExpectedAmount->lessThan($expectedAmount)) {
            throw new ApiBadRequestException('Tokens availability changed. Please adjust donation amount.');
        }

        if (Token::BTC_SYMBOL === $currency) {
            $donationAmount = $this->getBtcWorthInMintme($donationAmount);
        }

        if ($expectedAmount->greaterThan($minTokensAmount) && $tokensWorth->greaterThanOrEqual($donationAmount)) {
            // Token creator has available sell orders, enough for donation (using donation API)
            // Fee is taking by donation viabtc API
            $feeAmount = new Money(0, new Currency($currency));

            // Convert BTC to MINTME. viabtc API using real user's funds in MINTME only.
            if (Token::BTC_SYMBOL === $currency) {
                $this->sendAmountFromUserToUser($donorUser, $amountObj, $donorUser, $donationAmount, Token::BTC_SYMBOL, Token::WEB_SYMBOL);
            }

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($donationAmount),
                $this->getFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );
            $this->saveDonation($donorUser, $tokenCreator, $currency, $amountObj, $feeAmount, $expectedAmount);
        } elseif ($expectedAmount->greaterThan($minTokensAmount) && $expectedAmount->isPositive() && $tokensWorth->lessThan($donationAmount)) {
            // Token creator has available sell orders, NOT enough for donation (using donation API)
            // So part of donation funds (equal to sell orders worth) is sending via donation viabtc API
            // Another part is sending from user to user
            if (Token::BTC_SYMBOL === $currency) {
                $tokensWorthInBtc = $this->getMintmeWorthInBtc($tokensWorth);
                // Convert BTC to MINTME. viabtc API using real user's funds in MINTME only.
                $this->sendAmountFromUserToUser($donorUser, $tokensWorthInBtc, $donorUser, $tokensWorth, Token::BTC_SYMBOL, Token::WEB_SYMBOL);
                $this->donationFetcher->makeDonation(
                    $donorUser->getId(),
                    $this->marketNameConverter->convert($market),
                    $this->moneyWrapper->format($tokensWorth),
                    $this->getFee(),
                    $this->moneyWrapper->format($expectedAmount),
                    $tokenCreator->getId()
                );

                // Calculate left funds and send from user to user.
                $donationAmountLeftInBtc = $amountObj->subtract($tokensWorthInBtc);
                $feeAmount = $this->calculateFee($donationAmountLeftInBtc);
                $amountToDonate = $donationAmountLeftInBtc->subtract($feeAmount);
                $this->sendAmountFromUserToUser($donorUser, $donationAmountLeftInBtc, $donorUser, $amountToDonate, Token::BTC_SYMBOL, Token::WEB_SYMBOL);
                $this->saveDonation($donorUser, $tokenCreator, $currency, $amountToDonate, $feeAmount, $expectedAmount);
            } else {
                $amountToSendManually = $donationAmount->subtract($tokensWorth);
                $this->donationFetcher->makeDonation(
                    $donorUser->getId(),
                    $this->marketNameConverter->convert($market),
                    $this->moneyWrapper->format($tokensWorth),
                    $this->getFee(),
                    $this->moneyWrapper->format($expectedAmount),
                    $tokenCreator->getId()
                );
                $feeAmount = $this->calculateFee($amountToSendManually);
                $amountToDonate = $amountToSendManually->subtract($feeAmount);
                $this->sendAmountFromUserToUser($donorUser, $amountToSendManually, $tokenCreator, $amountToDonate, $currency, $currency);
                $this->saveDonation($donorUser, $tokenCreator, $currency, $amountToDonate, $feeAmount, $expectedAmount);
            }
        } else {
            // Token creator has no sell orders available. Send funds from user to user.
            $feeAmount = $this->calculateFee($amountObj);
            $amountToDonate = $amountObj->subtract($feeAmount);
            $this->sendAmountFromUserToUser($donorUser, $amountObj, $tokenCreator, $amountToDonate, $currency, $currency);
            $this->saveDonation($donorUser, $tokenCreator, $currency, $amountToDonate, $feeAmount, $expectedAmount);
        }

        $this->balanceHandler->updateUserTokenRelation($donorUser, $token);
    }

    public function getTokensWorth(string $tokensWorth, string $currency): string
    {
        if (Token::BTC_SYMBOL === $currency) {
            // Get tokens worth in BTC currency
            $tokensWorth = $this->moneyWrapper->parse($tokensWorth, $currency);
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

    private function getMintmeWorthInBtc(Money $amountInBtc): Money
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        return $this->moneyWrapper->convert(
            $amountInBtc,
            new Currency(Token::BTC_SYMBOL),
            new FixedExchange([
                Token::WEB_SYMBOL => [
                    Token::BTC_SYMBOL => $rates[Token::WEB_SYMBOL][Token::BTC_SYMBOL],
                ],
            ])
        );
    }

    private function getBtcWorthInMintme(Money $amountInMintme): Money
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        return $this->moneyWrapper->convert(
            $amountInMintme,
            new Currency(Token::WEB_SYMBOL),
            new FixedExchange([
                Token::BTC_SYMBOL => [
                    Token::WEB_SYMBOL => 1 / $rates[Token::WEB_SYMBOL][Token::BTC_SYMBOL],
                ],
            ])
        );
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
