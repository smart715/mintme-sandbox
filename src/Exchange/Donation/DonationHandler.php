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
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
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
    private ParameterBagInterface $parameterBag;
    private TraderInterface $trader;

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
        ParameterBagInterface $parameterBag,
        TraderInterface $trader
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
        $this->parameterBag = $parameterBag;
        $this->trader = $trader;
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
            $this->moneyWrapper->format($this->subtractOrdersFeeFromAmount($amountObj)),
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
        $cryptoMarket = new Market(
            $this->cryptoManager->findBySymbol($currency),
            $this->cryptoManager->findBySymbol(Symbols::WEB)
        );

        if (!$isDonationInMintme) {
            // Convert sum of donation in any Crypto to MINTME
            $pendingSellOrders = $this->marketHandler->getAllPendingSellOrders($cryptoMarket);
            $donationMintmeAmount = $this->getCryptoWorthInMintme($pendingSellOrders, $donationMintmeAmount);
        }

        $donationMintmeAmountWithSubstractedOrdersFee = $this->subtractOrdersFeeFromAmount($donationMintmeAmount);

            // Check how many tokens will recieve user and how many MINTME he should spend
        $checkDonationResult = $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($donationMintmeAmountWithSubstractedOrdersFee),
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
                $this->executeMarketOrders(
                    $donorUser,
                    $donationMintmeAmount,
                    $pendingSellOrders,
                    $cryptoMarket
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

            $feeFromDonationAmount = $this->calculateFee($tokensWorthInMintme);
            $tokensWorthInMintmeWithSubtractedFee = $tokensWorthInMintme->subtract($feeFromDonationAmount);
            $amountToDonate = $donationMintmeAmountWithSubstractedOrdersFee
                ->subtract($tokensWorthInMintmeWithSubtractedFee);
            $donationAmountLeft = $donationMintmeAmountWithSubstractedOrdersFee->subtract($tokensWorthInMintme);

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
                $donationWithOrdersFee = $this->subtractOrdersFeeFromAmount($donationMintmeAmount);

                $this->sendAmountFromUserToUser(
                    $donorUser,
                    $donationWithOrdersFee,
                    $tokenCreator,
                    $donationWithOrdersFee->subtract($donationWithFee),
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

            $this->moneyWrapper->format($totalMintmeToExecute);
            $price = $sellOrder->getPrice();
            $amount = $sellOrder->getAmount()->greaterThan($totalMintmeToExecute)
                ? $totalMintmeToExecute->subtract($executedSum)
                : $sellOrder->getAmount();

            $fee = $this->moneyWrapper->parse((string)$this->parameterBag->get('maker_fee_rate'), Symbols::TOK);
            $order = new Order(
                null,
                $donator,
                null,
                $market,
                $amount,
                Order::BUY_SIDE,
                $price,
                Order::PENDING_STATUS,
                $fee,
                null,
                null,
                $donator->getReferencer() ? (int)$donator->getReferencer()->getId() : 0
            );

            $this->trader->placeOrder($order, false);

            $executedSum = $executedSum->add($amount);
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
     * @return Money
     * @throws ApiBadRequestException
     */
    private function getCryptoWorthInMintme(array $pendingSellOrders, Money $amount): Money
    {
        $donatinonAmount = $this->moneyWrapper->parse($this->moneyWrapper->format($amount), Symbols::WEB);
        $totalSum = new Money(0, new Currency(Symbols::WEB));
        $mintmeWorth = new Money(0, new Currency(Symbols::WEB));

        foreach ($pendingSellOrders as $sellOrder) {
            if ($totalSum->greaterThanOrEqual($donatinonAmount)) {
                break;
            }

            $order = $sellOrder->getAmount()->multiply(
                $this->moneyWrapper->format($sellOrder->getPrice())
            );
            $diff = $donatinonAmount->subtract($totalSum);

            if ($diff->greaterThan($order)) {
                $totalSum = $totalSum->add($order);
                $mintmeWorth = $mintmeWorth->add($sellOrder->getAmount());
            } else {
                $totalSum = $totalSum->add($sellOrder->getAmount()->multiply(
                    $this->moneyWrapper->format(
                        $diff->divide($this->moneyWrapper->format($sellOrder->getPrice()))
                    )
                ));
                $mintmeWorth = $mintmeWorth->add(
                    $diff->divide($this->moneyWrapper->format($sellOrder->getPrice()))
                );
            }
        }

        if ($totalSum->lessThan($donatinonAmount)) {
            throw new ApiBadRequestException('Crypto market doesn\'t have enough orders.');
        }

        return $mintmeWorth;
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

    private function subtractOrdersFeeFromAmount(Money $money): Money
    {
        return $money->subtract(
            $money->multiply($this->parameterBag->get('taker_fee_rate'))
        );
    }
}
