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
use App\Exchange\ExchangerInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Manager\CryptoManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DonationHandler implements DonationHandlerInterface
{
    private DonationFetcherInterface $donationFetcher;
    private MarketNameConverterInterface $marketNameConverter;
    private MoneyWrapperInterface $moneyWrapper;
    private CryptoRatesFetcherInterface $cryptoRatesFetcher;
    protected CryptoManagerInterface $cryptoManager;
    private BalanceHandlerInterface $balanceHandler;
    private DonationConfig $donationConfig;
    private EntityManagerInterface $em;
    private MarketHandlerInterface $marketHandler;
    private ExchangerInterface $exchanger;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        DonationFetcherInterface $donationFetcher,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        CryptoManagerInterface $cryptoManager,
        BalanceHandlerInterface $balanceHandler,
        DonationConfig $donationConfig,
        EntityManagerInterface $em,
        MarketHandlerInterface $marketHandler,
        ExchangerInterface $exchanger,
        ParameterBagInterface $parameterBag
    ) {
        $this->donationFetcher = $donationFetcher;
        $this->marketNameConverter = $marketNameConverter;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->cryptoManager = $cryptoManager;
        $this->balanceHandler = $balanceHandler;
        $this->donationConfig = $donationConfig;
        $this->em = $em;
        $this->marketHandler = $marketHandler;
        $this->exchanger = $exchanger;
        $this->parameterBag = $parameterBag;
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

        if (Symbols::BTC === $currency || Symbols::ETH === $currency || Symbols::USDC === $currency) {
            $pendingSellOrders = $this->marketHandler->getAllPendingSellOrders(
                new Market(
                    $this->cryptoManager->findBySymbol($currency),
                    $this->cryptoManager->findBySymbol(Symbols::WEB)
                )
            );
            $amountObj = $this->getCryptoWorthInMintme($pendingSellOrders, $amountObj);
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
    ): Donation {
        // Sum of donation in any crypto (MINTME, BTC, ETH, USDC)
        $amountInCrypto = $this->moneyWrapper->parse($donationAmount, $currency);

        // Check if user has enough balance
        $this->checkAmount($donorUser, $amountInCrypto, $currency);

        /** @var Token $token */
        $token = $market->getQuote();
        /** @var User tokenCreator */
        $tokenCreator = $token->getProfile()->getUser();

        // Summary in MINTME of all sell orders
        $sellOrdersSummary = $this->moneyWrapper->parse($sellOrdersSummary, Symbols::WEB);

        // Amount of tokens which user receive after donation
        $expectedAmount = $this->moneyWrapper->parse($expectedTokensAmount, Symbols::WEB);
        $minTokensAmount = $this->donationConfig->getMinTokensAmount();

        $donationMintmeAmount = $amountInCrypto;
        $pendingSellOrders = [];
        $isDonationInMintme = Symbols::WEB === $currency;

        if (!$isDonationInMintme) {
            $cryptoMarket = new Market(
                $this->cryptoManager->findBySymbol($currency),
                $this->cryptoManager->findBySymbol(Symbols::WEB)
            );
            // Convert sum of donation in any Crypto to MINTME
            $pendingSellOrders = $this->marketHandler->getAllPendingSellOrders($cryptoMarket);
            $donationMintmeAmount = $this->getCryptoWorthInMintme($pendingSellOrders, $donationMintmeAmount);
        }

        // Check how many tokens will recieve user and how many MINTME he should spend
        $checkDonationResult = $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($donationMintmeAmount),
            $this->donationConfig->getFee(),
            $tokenCreator->getId()
        );

        $currentExpectedAmount = $this->moneyWrapper->parse(
            $checkDonationResult->getExpectedTokens(),
            Symbols::WEB
        );

        $tokensWorthInMintme = $this->moneyWrapper->parse(
            $checkDonationResult->getTokensWorth(),
            Symbols::WEB
        );

        // Check expected tokens amount.
        if (!$currentExpectedAmount->equals($expectedAmount)) {
            throw new ApiBadRequestException('Tokens availability changed. Please adjust donation amount.');
        }

        $twoWayDonation = $expectedAmount->greaterThanOrEqual($minTokensAmount)
            && $expectedAmount->isPositive() && $sellOrdersSummary->lessThan($donationMintmeAmount);

        if ($expectedAmount->greaterThanOrEqual($minTokensAmount) &&
            $sellOrdersSummary->greaterThanOrEqual($donationMintmeAmount)
        ) {
            // Donate using donation viabtc API (token creator has available sell orders)
            if (!$isDonationInMintme) {
                $this->executeMarketOrders($donorUser, $donationMintmeAmount, $pendingSellOrders, $cryptoMarket);
            }

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorthInMintme),
                $this->donationConfig->getFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );
        } elseif (!$isDonationInMintme && $twoWayDonation) {
            // Donate BTC using donation viabtc API AND donation from user to user.
            $this->executeMarketOrders($donorUser, $donationMintmeAmount, $pendingSellOrders, $cryptoMarket);

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorthInMintme),
                $this->donationConfig->getFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );

            $donationAmountLeft = $donationMintmeAmount->subtract($sellOrdersSummary);
            $feeFromDonationAmount = $this->calculateFee($donationAmountLeft);
            $amountToDonate = $donationAmountLeft->subtract($feeFromDonationAmount);

            $this->sendAmountFromUserToUser(
                $donorUser,
                $donationAmountLeft,
                $tokenCreator,
                $amountToDonate,
                Symbols::WEB,
                Symbols::WEB
            );
        } elseif ($isDonationInMintme && $twoWayDonation) {
            // Donate MINTME using donation viabtc API AND donation from user to user.
            $amountToSendManually = $donationMintmeAmount->subtract($tokensWorthInMintme);

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
            if ($isDonationInMintme) {
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
            } else {
                $this->executeMarketOrders($donorUser, $donationMintmeAmount, $pendingSellOrders, $cryptoMarket);
                $donationWithFee = $this->calculateFee($donationMintmeAmount);

                $this->sendAmountFromUserToUser(
                    $donorUser,
                    $donationWithFee,
                    $tokenCreator,
                    $donationWithFee,
                    Symbols::WEB,
                    Symbols::WEB
                );
            }
        }

        $feeAmount = $this->calculateFee($amountInCrypto);
        $donation = $this->saveDonation(
            $donorUser,
            $tokenCreator,
            $currency,
            $amountInCrypto,
            $feeAmount,
            $expectedAmount,
            $token
        );

        $this->balanceHandler->updateUserTokenRelation($donorUser, $token);

        return $donation;
    }

    /**
     * @param User $donator
     * @param Money $totalMintmeToExecute
     * @param Order[] $pendingSellOrders
     * @param Market $market
     */
    private function executeMarketOrders(
        User $donator,
        Money $totalMintmeToExecute,
        array $pendingSellOrders,
        Market $market
    ): void {
        $executedSum = new Money('0', new Currency(Symbols::WEB));

        foreach ($pendingSellOrders as $sellOrder) {
            if ($executedSum->greaterThanOrEqual($totalMintmeToExecute)) {
                break;
            }

            $price = $sellOrder->getPrice();
            $amount = $sellOrder->getAmount()->greaterThan($totalMintmeToExecute)
                ? $totalMintmeToExecute->subtract($executedSum)
                : $sellOrder->getAmount();

            $this->exchanger->placeOrder(
                $donator,
                $market,
                $this->moneyWrapper->format($amount),
                $this->moneyWrapper->format($price),
                false,
                Order::BUY_SIDE
            );

            $executedSum->add($amount->multiply($this->moneyWrapper->format($price)));
        }
    }

    public function saveDonation(
        User $donor,
        User $tokenCreator,
        string $currency,
        Money $amount,
        Money $feeAmount,
        Money $tokenAmount,
        Token $token
    ): Donation {
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

        return $donation;
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

    /**
     * @param Order[] $pendingSellOrders
     * @param Money $amount
     * @param string $cryptoSymbol
     * @return Money
     * @throws ApiBadRequestException
     */
    private function getCryptoWorthInMintme(array $pendingSellOrders, Money $amount): Money
    {
        $donatinonAmount = $this->moneyWrapper->parse($this->moneyWrapper->format($amount), Symbols::WEB);
        $totalSum = new Money(0, new Currency(Symbols::WEB));

        foreach ($pendingSellOrders as $sellOrder) {
            if ($totalSum->greaterThanOrEqual($donatinonAmount)) {
                break;
            }

            $order = $sellOrder->getPrice()->multiply(
                $this->moneyWrapper->format($sellOrder->getAmount())
            );

            $totalSum = $order->greaterThan($totalSum)
                ? $totalSum->add(
                    $donatinonAmount->subtract($totalSum)->divide($this->moneyWrapper->format($sellOrder->getPrice()))
                )
                : $totalSum->add($order);
        }

        if ($totalSum->lessThan($donatinonAmount)) {
            throw new ApiBadRequestException('Market doesn\'t have enough orders.');
        }

        return $totalSum;
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

        if (Symbols::BTC === $currency) {
            $minBtcAmount = $this->donationConfig->getMinBtcAmount();

            if ($amount->lessThan($minBtcAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } elseif (Symbols::WEB === $currency) {
            $minMintmeAmount = $this->donationConfig->getMinMintmeAmount();

            if ($amount->lessThan($minMintmeAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } elseif (Symbols::ETH === $currency) {
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
}
