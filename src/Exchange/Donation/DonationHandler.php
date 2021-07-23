<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\Donation\Model\CheckDonationResult;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;

class DonationHandler implements DonationHandlerInterface
{
    private DonationFetcherInterface $donationFetcher;
    private MarketNameConverterInterface $marketNameConverter;
    private MoneyWrapperInterface $moneyWrapper;
    private CryptoManagerInterface $cryptoManager;
    private BalanceHandlerInterface $balanceHandler;
    private QuickTradeConfig $quickTradeConfig;
    private EntityManagerInterface $em;
    private MarketHandlerInterface $marketHandler;
    private TraderInterface $trader;
    private MarketFactoryInterface $marketFactory;

    private const ANOTHER_DONATION_SYMBOLS = [
        Symbols::BTC,
        Symbols::ETH,
        Symbols::USDC,
        Symbols::BNB,
    ];

    public function __construct(
        DonationFetcherInterface $donationFetcher,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManagerInterface $cryptoManager,
        BalanceHandlerInterface $balanceHandler,
        QuickTradeConfig $quickTradeConfig,
        EntityManagerInterface $em,
        MarketHandlerInterface $marketHandler,
        TraderInterface $trader,
        MarketFactoryInterface $marketFactory
    ) {
        $this->donationFetcher = $donationFetcher;
        $this->marketNameConverter = $marketNameConverter;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoManager = $cryptoManager;
        $this->balanceHandler = $balanceHandler;
        $this->quickTradeConfig = $quickTradeConfig;
        $this->em = $em;
        $this->marketHandler = $marketHandler;
        $this->trader = $trader;
        $this->marketFactory = $marketFactory;
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

        if (in_array($currency, self::ANOTHER_DONATION_SYMBOLS, true)) {
            $pendingSellOrders = $this->marketHandler->getAllPendingSellOrders(
                $this->marketFactory->create(
                    $this->cryptoManager->findBySymbol($currency),
                    $this->cryptoManager->findBySymbol(Symbols::WEB)
                )
            );
            $amountObj = $this->getCryptoWorthInMintme($pendingSellOrders, $amountObj);
        }

        return $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($amountObj),
            $this->quickTradeConfig->getDonationFee(),
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
        $tokenCreator = $token->getProfile()->getUser();

        // Summary in MINTME of all sell orders
        $sellOrdersSummary = $this->moneyWrapper->parse($sellOrdersSummary, Symbols::WEB);

        // Amount of tokens which user receive after donation
        $expectedAmount = $this->moneyWrapper->parse($expectedTokensAmount, Symbols::WEB);
        $minTokensAmount = $this->quickTradeConfig->getMinTokensAmount();

        $donationMintmeAmount = $amountInCrypto;
        $isDonationInMintme = Symbols::WEB === $currency;
        $cryptoMarket = $this->marketFactory->create(
            $this->cryptoManager->findBySymbol($currency),
            $this->cryptoManager->findBySymbol(Symbols::WEB)
        );

        if (!$isDonationInMintme) {
            // Convert sum of donation in any Crypto to MINTME
            $pendingSellOrders = $this->marketHandler->getAllPendingSellOrders($cryptoMarket);
            $donationMintmeAmount = $this->getCryptoWorthInMintme($pendingSellOrders, $donationMintmeAmount);
        }

        // Check how many tokens will receive user and how many MINTME he should spend
        $checkDonationResult = $this->donationFetcher->checkDonation(
            $this->marketNameConverter->convert($market),
            $this->moneyWrapper->format($donationMintmeAmount),
            $this->quickTradeConfig->getDonationFee(),
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

        $mintmeFee = $this->calculateFee($donationMintmeAmount);

        if ($expectedAmount->greaterThanOrEqual($minTokensAmount) &&
            $sellOrdersSummary->greaterThanOrEqual($donationMintmeAmount)
        ) {
            // Donate using donation viabtc API (token creator has available sell orders)
            if (!$isDonationInMintme) {
                $this->executeMarketOrders(
                    $donorUser,
                    $amountInCrypto,
                    $cryptoMarket
                );
            }

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorthInMintme),
                $this->quickTradeConfig->getDonationFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );
        } elseif (!$isDonationInMintme && $twoWayDonation) {
            // Donate BTC using donation viabtc API AND donation from user to user.
            $this->executeMarketOrders($donorUser, $amountInCrypto, $cryptoMarket);

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorthInMintme),
                $this->quickTradeConfig->getDonationFee(),
                $this->moneyWrapper->format($expectedAmount),
                $tokenCreator->getId()
            );

            $donationAmountLeft = $donationMintmeAmount->subtract($tokensWorthInMintme);
            $amountToDonate = $donationMintmeAmount
                ->subtract($tokensWorthInMintme)
                ->subtract($this->calculateFee($donationAmountLeft));

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
                $this->quickTradeConfig->getDonationFee(),
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
                $this->executeMarketOrders($donorUser, $amountInCrypto, $cryptoMarket);
                $donationWithSubtractedFee = $donationMintmeAmount
                    ->subtract($this->calculateFee($donationMintmeAmount));

                $this->sendAmountFromUserToUser(
                    $donorUser,
                    $donationMintmeAmount,
                    $tokenCreator,
                    $donationWithSubtractedFee,
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
            $token,
            $donationMintmeAmount,
            $mintmeFee
        );

        $this->balanceHandler->updateUserTokenRelation($donorUser, $token);

        return $donation;
    }

    private function executeMarketOrders(
        User $donator,
        Money $amountInCrypto,
        Market $market
    ): void {
        $order = new Order(
            null,
            $donator,
            null,
            $market,
            $amountInCrypto,
            Order::BUY_SIDE,
            $this->moneyWrapper->parse('0', $market->getQuote()->getSymbol()),
            Order::PENDING_STATUS,
            $this->moneyWrapper->parse('0', $market->getQuote()->getSymbol()),
            null,
            null,
            $donator->getReferencer() ? (int)$donator->getReferencer()->getId() : 0
        );

        $this->trader->executeOrder($order);
    }

    public function saveDonation(
        User $donor,
        User $tokenCreator,
        string $currency,
        Money $amount,
        Money $feeAmount,
        Money $tokenAmount,
        Token $token,
        Money $mintmeAmount,
        Money $mintmeFeeAmount
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

        if (Symbols::WEB !== $currency) {
            $donation
                ->setMintmeAmount($mintmeAmount)
                ->setMintmeFeeAmount($mintmeFeeAmount);
        }

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
            $cryptos[$withdrawCurrency],
            $donationAmount->negative(),
            'donation'
        );
        $this->balanceHandler->update(
            $depositToUser,
            $cryptos[$depositCurrency],
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
        $totalSum = $this->moneyWrapper->parse('0', Symbols::WEB);
        $mintmeWorth = $this->moneyWrapper->parse('0', Symbols::WEB);

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
                $order = $diff->divide($this->moneyWrapper->format($sellOrder->getPrice()));

                $totalSum = $totalSum->add($diff);
                $mintmeWorth = $mintmeWorth->add($order);
            }
        }

        if ($totalSum->lessThan($donatinonAmount)) {
            throw new ApiBadRequestException('Crypto market doesn\'t have enough orders.');
        }

        return $mintmeWorth;
    }

    private function calculateFee(Money $amount): Money
    {
        $fee = $this->quickTradeConfig->getDonationFee();

        return $amount->multiply($fee)->divide(1 + (float)$fee);
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
            $minBtcAmount = $this->quickTradeConfig->getMinBtcAmount();

            if ($amount->lessThan($minBtcAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } elseif (Symbols::WEB === $currency) {
            $minMintmeAmount = $this->quickTradeConfig->getMinMintmeAmount();

            if ($amount->lessThan($minMintmeAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } elseif (Symbols::ETH === $currency) {
            $minEthAmount = $this->quickTradeConfig->getMinEthAmount();

            if ($amount->lessThan($minEthAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } elseif (Symbols::BNB === $currency) {
            $minBnbAmount = $this->quickTradeConfig->getMinBnbAmount();

            if ($amount->lessThan($minBnbAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        } else {
            $minUsdcAmount = $this->quickTradeConfig->getMinUsdcAmount();

            if ($amount->lessThan($minUsdcAmount) || ($checkBalance && $user && $amount->greaterThan($balance))) {
                throw new ApiBadRequestException('Invalid donation amount.');
            }
        }
    }
}
