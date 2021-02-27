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
use App\Wallet\Money\MoneyWrapper;
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
        ?User $donorUser
    ): CheckDonationResult {
        $amountObj = $this->moneyWrapper->parse($amount, $currency);
        /** @var Token $token */
        $token = $market->getQuote();

        $this->checkAmount($donorUser, $amountObj, $currency, false);

        if (Token::BTC_SYMBOL === $currency || Token::ETH_SYMBOL === $currency || Token::USDC_SYMBOL === $currency) {
            $amountObj = $this->getCryptoWorthInMintme($amountObj, $currency);
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
        string $donationAmount,
        string $expectedTokensAmount,
        User $donorUser,
        string $sellOrdersSummary
    ): void {
        // Sum of donation in any crypto (MINTME, BTC, ETH, USDC)
        $amountInCrypto = $this->moneyWrapper->parse($donationAmount, $currency);

        // Check if user has enough balance
        $this->checkAmount($donorUser, $amountInCrypto, $currency);

        /** @var Token $token */
        $token = $market->getQuote();
        /** @var User tokenCreator */
        $tokenCreator = $token->getProfile()->getUser();

        // Summary in MINTME of all sell orders
        $sellOrdersSummary = $this->moneyWrapper->parse($sellOrdersSummary, Token::WEB_SYMBOL);

        // Amount of tokens which user receive after donation
        $expectedAmount = $this->moneyWrapper->parse($expectedTokensAmount, Token::WEB_SYMBOL);
        $minTokensAmount = $this->donationConfig->getMinTokensAmount();

        $donationAmount = $amountInCrypto;

        if (Token::BTC_SYMBOL === $currency || Token::ETH_SYMBOL === $currency || Token::USDC_SYMBOL === $currency) {
            // Convert sum of donation in any Crypto to MINTME
            $donationAmount = $this->getCryptoWorthInMintme($donationAmount, $currency);
        }

        // Check how many tokens will recieve user and how many MINTME he should spend
        $checkDonationResult = $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($donationAmount),
            $this->donationConfig->getFee(),
            $tokenCreator->getId()
        );

        $currentExpectedAmount = $this->moneyWrapper->parse(
            $checkDonationResult->getExpectedTokens(),
            Token::WEB_SYMBOL
        );

        $tokensWorthInMintme = $this->moneyWrapper->parse(
            $checkDonationResult->getTokensWorth(),
            Token::WEB_SYMBOL
        );
        $tokensWorthInMintmeWithFee = $tokensWorthInMintme->divide(1 + floatval($this->donationConfig->getFee()));

        // Check expected tokens amount.
        if (!$currentExpectedAmount->equals($expectedAmount)) {
            throw new ApiBadRequestException('Tokens availability changed. Please adjust donation amount.');
        }

        $twoWayDonation = $expectedAmount->greaterThanOrEqual($minTokensAmount)
            && $expectedAmount->isPositive() && $sellOrdersSummary->lessThan($donationAmount);

        $isDonationInMintme = Token::WEB_SYMBOL === $currency;

        if ($expectedAmount->greaterThanOrEqual($minTokensAmount) &&
            $sellOrdersSummary->greaterThanOrEqual($donationAmount)
        ) {
            // Donate using donation viabtc API (token creator has available sell orders)
            if (!$isDonationInMintme) {
                $this->sendAmountFromUserToUser(
                    $donorUser,
                    // Sum of donation in any crypto (ETH, BTC)
                    $amountInCrypto,
                    $donorUser,
                    // Sum of donation in MINTME
                    $tokensWorthInMintme,
                    $currency,
                    Token::WEB_SYMBOL
                );
            }

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorthInMintme),
                $this->donationConfig->getFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );

            if (!$isDonationInMintme) {
                $this->sendAmountFromUserToUser(
                    $tokenCreator,
                    $tokensWorthInMintmeWithFee,
                    $tokenCreator,
                    $this->getMintmeWorthInCrypto($tokensWorthInMintmeWithFee, $currency),
                    Token::WEB_SYMBOL,
                    $currency
                );
            }
        } elseif (!$isDonationInMintme && $twoWayDonation) {
            // Donate BTC using donation viabtc API AND donation from user to user.
            $sellOrdersSummaryWithFee = $this->calculateAmountWithFee($sellOrdersSummary);
            $sellOrdersSummaryInCrypto = $this->getMintmeWorthInCrypto($sellOrdersSummaryWithFee, $currency);
            $this->sendAmountFromUserToUser(
                $donorUser,
                $sellOrdersSummaryInCrypto,
                $donorUser,
                $tokensWorthInMintme,
                $currency,
                Token::WEB_SYMBOL
            );

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorthInMintme),
                $this->donationConfig->getFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );

            $donationAmountLeftInCrypto = $amountInCrypto->subtract($sellOrdersSummaryInCrypto);
            $feeFromDonationAmount = $this->calculateFee($donationAmountLeftInCrypto);
            $amountToDonate = $donationAmountLeftInCrypto->subtract($feeFromDonationAmount);
            $this->sendAmountFromUserToUser(
                $donorUser,
                $donationAmountLeftInCrypto,
                $tokenCreator,
                $amountToDonate,
                $currency,
                $currency
            );

            $this->sendAmountFromUserToUser(
                $tokenCreator,
                $sellOrdersSummary,
                $tokenCreator,
                $this->getMintmeWorthInCrypto($sellOrdersSummary, $currency),
                Token::WEB_SYMBOL,
                $currency
            );
        } elseif ($isDonationInMintme && $twoWayDonation) {
            // Donate MINTME using donation viabtc API AND donation from user to user.
            $amountToSendManually = $donationAmount->subtract($tokensWorthInMintme);

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorthInMintme),
                $this->donationConfig->getFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );
            $feeFromDonationAmount = $this->calculateFee($amountToSendManually);
            $amountToDonate = $amountToSendManually->subtract($feeFromDonationAmount);
            $this->sendAmountFromUserToUser(
                $donorUser,
                $amountToSendManually,
                $tokenCreator,
                $amountToDonate,
                $currency,
                $currency
            );
        } else {
            // Donate (send) funds from user to user (token creator has no sell orders).
            $feeFromDonationAmount = $this->calculateFee($amountInCrypto);
            $amountToDonate = $amountInCrypto->subtract($feeFromDonationAmount);
            $this->sendAmountFromUserToUser(
                $donorUser,
                $amountInCrypto,
                $tokenCreator,
                $amountToDonate,
                $currency,
                $currency
            );
        }

        $feeAmount = $this->calculateFee($amountInCrypto);
        $this->saveDonation($donorUser, $tokenCreator, $currency, $amountInCrypto, $feeAmount, $expectedAmount, $token);
        $this->balanceHandler->updateUserTokenRelation($donorUser, $token);
    }

    public function getTokensWorth(string $tokensWorth, string $currency): string
    {
        if (Token::BTC_SYMBOL === $currency || Token::ETH_SYMBOL === $currency || Token::USDC_SYMBOL === $currency) {
            $tokensWorth = $this->moneyWrapper->parse($tokensWorth, Token::WEB_SYMBOL);
            $tokensWorthInCrypto = $this->getMintmeWorthInCrypto($tokensWorth, $currency);

            return $this->moneyWrapper->format($tokensWorthInCrypto);
        }

        return $tokensWorth;
    }

    public function saveDonation(
        User $donor,
        User $tokenCreator,
        string $currency,
        Money $amount,
        Money $feeAmount,
        Money $tokenAmount,
        Token $token
    ): void {
        $donation = new Donation();
        $donation
            ->setDonor($donor)
            ->setTokenCreator($tokenCreator)
            ->setCurrency($currency)
            ->setAmount($amount)
            ->setFeeAmount($feeAmount)
            ->setTokenAmount($tokenAmount)
            ->setToken($token)
        ;

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

    private function getMintmeWorthInCrypto(Money $amountInMintme, string $cryptoSymbol): Money
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        if (Token::USDC_SYMBOL === $cryptoSymbol) {
            $usdcRate = 1 / $rates[Token::USDC_SYMBOL][MoneyWrapper::USD_SYMBOL]
                * $rates[Token::WEB_SYMBOL][MoneyWrapper::USD_SYMBOL];
            $rates[Token::WEB_SYMBOL][$cryptoSymbol] = $usdcRate;
        }

        return $this->moneyWrapper->convert(
            $amountInMintme,
            new Currency($cryptoSymbol),
            new FixedExchange($rates)
        );
    }

    private function getCryptoWorthInMintme(Money $amount, string $cryptoSymbol): Money
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        if (Token::USDC_SYMBOL === $cryptoSymbol) {
            $usdcRate = 1 / $rates[Token::USDC_SYMBOL][MoneyWrapper::USD_SYMBOL]
                * $rates[Token::WEB_SYMBOL][MoneyWrapper::USD_SYMBOL];
            $rates[$cryptoSymbol][Token::WEB_SYMBOL] = 1 / $usdcRate;
        } else {
            $rates[$cryptoSymbol][Token::WEB_SYMBOL] = 1 / $rates[Token::WEB_SYMBOL][$cryptoSymbol];
        }

        return $this->moneyWrapper->convert(
            $amount,
            new Currency(Token::WEB_SYMBOL),
            new FixedExchange($rates)
        );
    }

    private function calculateFee(Money $amount): Money
    {
        return $amount->multiply($this->donationConfig->getFee());
    }

    private function checkAmount(?User $user, Money $amount, string $currency, bool $checkBalance = true): void
    {
        $balance = $checkBalance && $user
            ? $this->balanceHandler->balance(
                $user,
                Token::getFromSymbol($currency)
            )->getAvailable()
            : null;

        if (Token::BTC_SYMBOL === $currency) {
            $minBtcAmount = $this->donationConfig->getMinBtcAmount();

            if ($amount->lessThan($minBtcAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } elseif (Token::WEB_SYMBOL === $currency) {
            $minMintmeAmount = $this->donationConfig->getMinMintmeAmount();

            if ($amount->lessThan($minMintmeAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } elseif (Token::ETH_SYMBOL === $currency) {
            $minEthAmount = $this->donationConfig->getMinEthAmount();

            if ($amount->lessThan($minEthAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } else {
            $minUsdcAmount = $this->donationConfig->getMinUsdcAmount();

            if ($amount->lessThan($minUsdcAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        }
    }

    private function calculateAmountWithFee(Money $amount): Money
    {
        $divisor = 1 - (float)$this->donationConfig->getFee();

        return $amount->divide($divisor);
    }
}
