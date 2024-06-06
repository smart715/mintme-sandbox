<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\AMQP\MarketAMQPInterface;
use App\Communications\Exception\FetchException;
use App\Entity\Crypto;
use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\QuickTradeException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\CheckTradeResult;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\NewInvestorNotificationStrategy;
use App\Notifications\Strategy\NotificationContext;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\CryptoCalculator;
use App\Utils\NotificationTypes;
use App\Utils\Symbols;
use App\Utils\Validator\ValidatorInterface;
use App\Utils\ValidatorFactoryInterface;
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
    private ExchangerInterface $exchanger;
    private MarketFactoryInterface $marketFactory;
    private ValidatorFactoryInterface $validatorFactory;
    private MarketAMQPInterface $marketProducer;
    private UserActionLogger $logger;
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;
    private DonationCheckerInterface $donationChecker;
    private CryptoCalculator $cryptoCalculator;
    private float $referralFee;

    public function __construct(
        DonationFetcherInterface $donationFetcher,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManagerInterface $cryptoManager,
        BalanceHandlerInterface $balanceHandler,
        QuickTradeConfig $quickTradeConfig,
        EntityManagerInterface $em,
        MarketHandlerInterface $marketHandler,
        ExchangerInterface $exchanger,
        MarketFactoryInterface $marketFactory,
        ValidatorFactoryInterface $validatorFactory,
        MarketAMQPInterface $marketProducer,
        UserActionLogger $logger,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        DonationCheckerInterface $donationChecker,
        CryptoCalculator $cryptoCalculator,
        float $referralFee
    ) {
        $this->donationFetcher = $donationFetcher;
        $this->marketNameConverter = $marketNameConverter;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoManager = $cryptoManager;
        $this->balanceHandler = $balanceHandler;
        $this->quickTradeConfig = $quickTradeConfig;
        $this->em = $em;
        $this->marketHandler = $marketHandler;
        $this->exchanger = $exchanger;
        $this->marketFactory = $marketFactory;
        $this->validatorFactory = $validatorFactory;
        $this->marketProducer = $marketProducer;
        $this->logger = $logger;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->donationChecker = $donationChecker;
        $this->cryptoCalculator = $cryptoCalculator;
        $this->referralFee = $referralFee;
    }

    public function checkDonation(
        Market $market,
        string $amount,
        ?User $donorUser
    ): CheckTradeResult {
        /** @var Crypto $base */
        $base = $market->getBase();
        /** @var Token $token */
        $token = $market->getQuote();

        $realAmountObj = $this->moneyWrapper->parse($amount, $base->getMoneySymbol());
        $amountObj = $realAmountObj;

        $useCryptoMarket = Symbols::WEB !== $base->getSymbol() && !$token->containsExchangeCrypto($base);

        if ($useCryptoMarket) {
            $amountObj = $this->cryptoCalculator->getMintmeWorth($amountObj, true);

            $market->setBase($this->cryptoManager->findBySymbol(Symbols::WEB));
        }

        $tokenCreator = $token->getProfile()->getUser();
        $sellOrdersSummary = $this->moneyWrapper->parse(
            $this->marketHandler->getSellOrdersSummary($market, $tokenCreator)->getBaseAmount(),
            $market->getBase()->getSymbol(),
        );

        // with feeRates in [0.001, 0.003, 0.005, 0.007, 0.009] use two way donation for all cases except sell orders is zero
        $checkDonationResult = !$sellOrdersSummary->isZero()
            ? $this->donationChecker->checkTwoWayDonation($market, $amountObj, $tokenCreator)
            : $this->donationChecker->checkOneWayDonation($market, $amountObj, $tokenCreator);

        $expectedAmount = $checkDonationResult->getExpectedTokensAmount();
        $worth = $checkDonationResult->getExpectedTokensWorth();

        if ($useCryptoMarket) {
            $price = $realAmountObj->divide($this->moneyWrapper->format($amountObj));

            $worth = $worth->multiply($this->moneyWrapper->format($price));
        }

        return new CheckTradeResult($expectedAmount, $worth);
    }

    public function makeDonation(
        Market $market,
        string $donationAmountInCrypto,
        string $expectedTokensAmount,
        User $donorUser
    ): Donation {
        /** @var Token $token */
        $token = $market->getQuote();
        $tokenCreator = $token->getProfile()->getUser();

        // Amount of tokens which user receive after donation
        $expectedTokensAmount = $this->moneyWrapper->parse($expectedTokensAmount, Symbols::TOK);
        $minTokensAmount = $this->moneyWrapper->parse(
            $this->moneyWrapper->format($this->quickTradeConfig->getMinAmountBySymbol(Symbols::TOK)),
            Symbols::TOK
        );

        /** @var Crypto $donationCrypto */
        $donationCrypto = $market->getBase();
        $amountInCrypto = $this->moneyWrapper->parse($donationAmountInCrypto, $donationCrypto->getSymbol());
        $isDonationInMintme = Symbols::WEB === $donationCrypto->getSymbol();
        $useMintmeMarket = !$isDonationInMintme && !$token->containsExchangeCrypto($donationCrypto);

        $donationAmount = $amountInCrypto;
        $cryptoMarket = null;

        $this->checkBalance($donorUser, $amountInCrypto, $donationCrypto);
        $this->checkMinimum($donationCrypto, $market, $donationAmountInCrypto);

        if ($useMintmeMarket) {
            $donationAmount = $this->cryptoCalculator->getMintmeWorth($donationAmount, true);

            /** @var Crypto $mintme */
            $mintme = $this->cryptoManager->findBySymbol(Symbols::WEB);
            $cryptoMarket = $this->marketFactory->create($donationCrypto, $mintme);

            $market->setBase($mintme);
        }

        // Summary in donation currency of all sell orders from token creator
        $sellOrdersSummary = $this->moneyWrapper->parse(
            $this->marketHandler->getSellOrdersSummary($market, $tokenCreator)->getBaseAmount(),
            $market->getBase()->getSymbol()
        );

        // with feeRates in [0.001, 0.003, 0.005, 0.007, 0.009] use two way donation for all cases except sell orders is zero
        $checkDonationResult = !$sellOrdersSummary->isZero()
            ? $this->donationChecker->checkTwoWayDonation($market, $donationAmount, $tokenCreator)
            : $this->donationChecker->checkOneWayDonation($market, $donationAmount, $tokenCreator);

        $currentExpectedAmount = $checkDonationResult->getExpectedTokensAmount();
        $tokensWorth = $checkDonationResult->getExpectedTokensWorth();

        $difference = $currentExpectedAmount->subtract($expectedTokensAmount)->absolute();
        $maxError = $this->moneyWrapper->parse('0.0001', Symbols::TOK);

        // Check expected tokens amount.
        if ($difference->greaterThan($maxError)) {
            throw QuickTradeException::availabilityChanged();
        }

        $expectedTokensAmount = $currentExpectedAmount;

        $twoWayDonation = $expectedTokensAmount->greaterThanOrEqual($minTokensAmount)
            && !$expectedTokensAmount->isZero()
            && $sellOrdersSummary->lessThan($donationAmount);

        $receiverFeeAmount = $this->calculateFee($donationAmount);

        $type = null;

        if ($expectedTokensAmount->greaterThanOrEqual($minTokensAmount) &&
            $sellOrdersSummary->greaterThanOrEqual($donationAmount)
        ) {
            // Donate using donation viabtc API (token creator has available sell orders)
            $type = Donation::TYPE_FULL_BUY;

            if ($useMintmeMarket) {
                $this->executeMarketOrders(
                    $donorUser,
                    $amountInCrypto,
                    $cryptoMarket
                );
            }

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorth),
                $this->quickTradeConfig->getBuyTokenFee(),
                $this->moneyWrapper->format($expectedTokensAmount),
                $tokenCreator->getId()
            );
        } elseif (!$isDonationInMintme && $twoWayDonation) {
            // Donate BTC using donation viabtc API AND donation from user to user.
            $type = Donation::TYPE_PARTIAL;

            if ($useMintmeMarket) {
                $this->executeMarketOrders($donorUser, $amountInCrypto, $cryptoMarket);
            }

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorth),
                $this->quickTradeConfig->getBuyTokenFee(),
                $this->moneyWrapper->format($expectedTokensAmount),
                $tokenCreator->getId()
            );

            $donationAmountLeft = $donationAmount->subtract($tokensWorth);
            $amountToDonate = $donationAmount
                ->subtract($tokensWorth)
                ->subtract($this->calculateFee($donationAmountLeft));

            $this->sendAmountFromUserToUser(
                $donorUser,
                $donationAmountLeft,
                $tokenCreator,
                $amountToDonate,
                $market->getBase()->getSymbol(),
                $market->getBase()->getSymbol()
            );
        } elseif ($isDonationInMintme && $twoWayDonation) {
            // Donate MINTME using donation viabtc API AND donation from user to user.
            $type = Donation::TYPE_PARTIAL;

            $amountToSendManually = $donationAmount->subtract($tokensWorth);

            $this->donationFetcher->makeDonation(
                $donorUser->getId(),
                $this->marketNameConverter->convert($market),
                $this->moneyWrapper->format($tokensWorth),
                $this->quickTradeConfig->getBuyTokenFee(),
                $this->moneyWrapper->format($expectedTokensAmount),
                $tokenCreator->getId()
            );
            $feeFromDonationAmount = $this->calculateFee($amountToSendManually);
            $amountToDonate = $amountToSendManually->subtract($feeFromDonationAmount);
            $this->sendAmountFromUserToUser(
                $donorUser,
                $amountToSendManually,
                $tokenCreator,
                $amountToDonate,
                $donationCrypto->getSymbol(),
                $donationCrypto->getSymbol()
            );
        } else {
            // Donate (send) funds from user to user (token creator has no sell orders).
            $type = Donation::TYPE_FULL_DONATION;

            if ($isDonationInMintme) {
                $feeFromDonationAmount = $this->calculateFee($amountInCrypto);
                $amountToDonate = $amountInCrypto->subtract($feeFromDonationAmount);
                $this->sendAmountFromUserToUser(
                    $donorUser,
                    $amountInCrypto,
                    $tokenCreator,
                    $amountToDonate,
                    $donationCrypto->getSymbol(),
                    $donationCrypto->getSymbol()
                );
            } else {
                if ($useMintmeMarket) {
                    $this->executeMarketOrders($donorUser, $amountInCrypto, $cryptoMarket);
                }

                $donationWithSubtractedFee = $donationAmount
                    ->subtract($this->calculateFee($donationAmount));

                $this->sendAmountFromUserToUser(
                    $donorUser,
                    $donationAmount,
                    $tokenCreator,
                    $donationWithSubtractedFee,
                    $market->getBase()->getSymbol(),
                    $market->getBase()->getSymbol()
                );
            }
        }

        if (!$sellOrdersSummary->isZero() && !in_array($token, $donorUser->getTokens(), true)) {
            $extraData = [
                'profile' => $donorUser->getProfile()->getNickname(),
                'tokenName' => $token->getName(),
                'marketSymbol' => $donationCrypto->getSymbol(),
            ];
            $notificationType = NotificationTypes::NEW_INVESTOR;
            $strategy = new NewInvestorNotificationStrategy(
                $this->userNotificationManager,
                $this->mailer,
                $token,
                $notificationType,
                $extraData
            );
            $notificationContext = new NotificationContext($strategy);
            $notificationContext->sendNotification($tokenCreator);
        }

        $donationCurrency = $useMintmeMarket
            ? Symbols::WEB
            : $donationCrypto->getSymbol();

        if ($donorUser->getReferencer()) {
            $mintme = $this->cryptoManager->findBySymbol(Symbols::WEB);

            $referencerAmount = Symbols::WEB === $donationCurrency
                ? $receiverFeeAmount->multiply($this->referralFee)
                : $this->cryptoCalculator->getMintmeWorth($receiverFeeAmount->multiply($this->referralFee));

            $this->balanceHandler->deposit(
                $donorUser->getReferencer(),
                $mintme,
                $referencerAmount,
            );
        }

        $donation = $this->saveDonation(
            $donorUser,
            $tokenCreator,
            $donationCurrency,
            $donationAmount,
            $this->calculateFee($donationAmount),
            $expectedTokensAmount,
            $token,
            $donationAmount,
            $receiverFeeAmount,
            $donationCurrency,
            $type,
            $donorUser->getReferencer(),
            $referencerAmount ?? null
        );

        $this->balanceHandler->updateUserTokenRelation($donorUser, $token);

        $this->updateMarket($market, $donorUser);

        return $donation;
    }

    private function executeMarketOrders(
        User $donator,
        Money $amountInCrypto,
        Market $market
    ): void {
        $this->exchanger->executeOrder(
            $donator,
            $market,
            $this->moneyWrapper->format($amountInCrypto),
            Order::BUY_SIDE,
            '0'
        );
    }

    public function saveDonation(
        User $donor,
        User $tokenCreator,
        string $currency,
        Money $amount,
        Money $feeAmount,
        Money $tokenAmount,
        Token $token,
        Money $receiverAmount,
        Money $receiverFeeAmount,
        string $receiverCurrency,
        string $type,
        ?User $referencer = null,
        ?Money $referencerAmount = null
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
            ->setReceiverAmount($receiverAmount)
            ->setReceiverFeeAmount($receiverFeeAmount)
            ->setReceiverCurrency($receiverCurrency)
            ->setType($type);

        if ($referencer && $referencerAmount) {
            $donation
                ->setReferencer($referencer)
                ->setReferencerAmount($referencerAmount);
        }

        $this->em->persist($donation);
        $this->em->flush();

        return $donation;
    }

    private function updateMarket(Market $market, User $user): void
    {
        try {
            $this->marketProducer->send($market, $user);
        } catch (\Throwable $exception) {
            $this->logger->error(
                "[Donation] Failed to update '${market}' market status. Reason: {$exception->getMessage()}"
            );
        }
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

        try {
            $this->balanceHandler->beginTransaction();

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
        } catch (\Throwable $exception) {
            $this->balanceHandler->rollback();

            $this->logger->error(
                "[Donation] Failed to update user balance. Reason: {$exception->getMessage()}"
            );
        }
    }

    private function calculateFee(Money $amount): Money
    {
        $fee = $this->quickTradeConfig->getBuyTokenFee();

        return $amount->multiply($fee)->divide(1 + (float)$fee);
    }

    private function checkBalance(User $user, Money $amount, Crypto $crypto): void
    {
        $balance = $this->balanceHandler->balance($user, $crypto)->getAvailable();

        if ($amount->greaterThan($balance)) {
            throw QuickTradeException::insufficientBalance();
        }
    }

    private function checkMinimum(Crypto $crypto, Market $market, string $amount): void
    {
        $minimum = $this->quickTradeConfig->getMinAmountBySymbol(
            $crypto->getMoneySymbol()
        );

        $minimum = $this->moneyWrapper->format($minimum);

        $minValidators = $this->validators($crypto, $market, $amount, $minimum);

        foreach ($minValidators as $validator) {
            /** @var ValidatorInterface $validator */
            try {
                $validate = $validator->validate();
            } catch (FetchException $exception) {
                $this->logger->error(
                    "[Donation] Failed to fetch minimum amount for {$crypto->getSymbol()}. Reason: {$exception->getMessage()}"
                );

                continue;
            }

            if (!$validate) {
                throw QuickTradeException::minAmountValidator($validator->getMessage());
            }
        }
    }

    private function validators(Crypto $crypto, Market $market, string $amount, string $minimum): array
    {
        return [
            $this->validatorFactory->createMinTradableValidator($crypto, $market, $amount, $minimum),
            $this->validatorFactory->createMinUsdValidator($crypto, $amount),
        ];
    }
}
