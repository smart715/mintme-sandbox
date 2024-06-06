<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\Exception\FetchException;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\DonationEvent;
use App\Events\TokenEvents;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\CryptoCalculatorException;
use App\Exception\QuickTradeException;
use App\Exchange\Donation\DonationHandlerInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\QuickTraderInterface;
use App\Exchange\Trade\TradeResult;
use App\Logger\UserActionLogger;
use App\Manager\DeployNotificationManagerInterface;
use App\Security\DisabledServicesVoter;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\LockFactory;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Rest\Route("/api/quick-trade")
 */
class QuickTradeController extends APIController
{
    public const BUY = 'buy';
    public const SELL = 'sell';

    private const TYPE_DIRECT_BUY = 'direct-buy';
    private const TYPE_DIRECT_SELL = 'direct-sell';

    protected DonationHandlerInterface $donationHandler;
    protected MarketHandlerInterface $marketHandler;
    protected UserActionLogger $logger;
    protected EventDispatcherInterface $eventDispatcher;

    private LockFactory $lockFactory;
    private TranslatorInterface $translator;
    private QuickTraderInterface $quickTrader;
    protected SessionInterface $session;
    private DeployNotificationManagerInterface $deployNotificationManager;

    use ViewOnlyTrait;

    public function __construct(
        DonationHandlerInterface $donationHandler,
        MarketHandlerInterface $marketHandler,
        UserActionLogger $logger,
        LockFactory $lockFactory,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        QuickTraderInterface $quickTrader,
        SessionInterface $session,
        DeployNotificationManagerInterface $deployNotificationManager
    ) {
        $this->donationHandler = $donationHandler;
        $this->marketHandler = $marketHandler;
        $this->logger = $logger;
        $this->lockFactory = $lockFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->quickTrader = $quickTrader;
        $this->session = $session;
        $this->deployNotificationManager = $deployNotificationManager;
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/check/{base}/{quote}/{mode}/{amount}",
     *     name="check_quick_trade",
     *     options={"expose"=true},
     * )
     * @Rest\QueryParam(name="amount", allowBlank=false, description="Amount to invest.")
     * @Rest\QueryParam(
     *     name="mode",
     *     allowBlank=false,
     *     requirements="^(sell|buy)$"),
     *     description="Trade mode"
     */
    public function checkTrade(Market $market, string $mode, string $amount): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        try {
            /** @var User|null $user */
            $user = $this->getUser();

            $this->checkMarket($market);

            if (self::BUY === $mode) {
                $quote = $market->getQuote();

                if ($quote instanceof Token) {
                    // Buy (Donation) in token market
                    $checkResult = $this->donationHandler->checkDonation(
                        $market,
                        $amount,
                        $user
                    );
                    $tokenCreator = $quote->getProfile()->getUser();

                    $ordersSummary = $this->marketHandler
                        ->getSellOrdersSummary($market, $tokenCreator)
                        ->getBaseAmount();
                } else {
                    // Buy in coin market
                    $checkResult = $this->quickTrader->checkBuy($market, $amount);

                    $ordersSummary = $this->marketHandler
                        ->getSellOrdersSummary($market)
                        ->getBaseAmount();
                }
            } elseif (self::SELL === $mode) {
                $checkResult = $this->quickTrader->checkSell($market, $amount);
                $ordersSummary = $this->marketHandler->getBuyOrdersSummary($market)->getQuoteAmount();
            } else {
                throw QuickTradeException::invalidMode();
            }

            return $this->view([
                'amountToReceive' => $checkResult->getExpectedAmount(),
                'worth' => $checkResult->getWorth() ?? '0',
                'ordersSummary' => $ordersSummary,
            ]);
        } catch (\Throwable $ex) {
            if ($response = $this->handleException($ex)) {
                return $response;
            }

            $message = $ex->getMessage();

            $this->logger->error(
                '[check_quick_trade] Failed to check trade.',
                [
                    'message' => $message,
                    'code' => $ex->getCode(),
                    'mode' => $mode,
                    'market' => $market,
                    'amount' => $amount,
                ]
            );

            return $this->view([
                'error' => $message,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

      /**
     * @Rest\View()
     * @Rest\Get(
     *     "/check-trade-reversed/{base}/{quote}/{mode}/{amountToReceive}",
     *     name="check_quick_trade_reversed",
     *     options={"expose"=true},
     * )
     * @Rest\QueryParam(name="amountToReceive", allowBlank=false, description="Amount to receive.")
     * @Rest\QueryParam(
     *     name="mode",
     *     allowBlank=false,
     *     requirements="^(sell|buy)$"),
     *     requirements="^(sell|buy)$,
     *     description="Trade mode"
     * )
     */
    public function checkTradeReversed(Market $market, string $mode, string $amountToReceive): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        try {
            $this->checkMarket($market);

            if (self::BUY === $mode) {
                $quote = $market->getQuote();

                if ($quote instanceof Token) {
                    // Buy (Donation) in token market
                    $checkResult = $this->quickTrader->checkDonationReversed($market, $amountToReceive);
                    $tokenCreator = $quote->getProfile()->getUser();
                    $ordersSummary = $this->marketHandler
                        ->getSellOrdersSummary($market, $tokenCreator)
                        ->getBaseAmount();
                } else {
                    // Buy in coin market
                    $checkResult = $this->quickTrader->checkBuyReversed($market, $amountToReceive);
                    $ordersSummary = $this->marketHandler
                        ->getSellOrdersSummary($market)
                        ->getBaseAmount();
                }
            } elseif (self::SELL === $mode) {
                $checkResult = $this->quickTrader->checkSellReversed($market, $amountToReceive);
                $ordersSummary = $this->marketHandler->getBuyOrdersSummary($market)->getQuoteAmount();
            } else {
                throw QuickTradeException::invalidMode();
            }

            return $this->view([
                'amount' => $checkResult->getExpectedAmount(),
                'left' => $checkResult->getWorth() ?? '0',
                'ordersSummary' => $ordersSummary,
            ]);
        } catch (\Throwable $ex) {
            if ($response = $this->handleException($ex)) {
                return $response;
            }

            $message = $ex->getMessage();

            $this->logger->error(
                '[check_quick_trade] Failed to check reversed trade.',
                [
                    'message' => $message,
                    'code' => $ex->getCode(),
                    'mode' => $mode,
                    'market' => $market,
                    'amountToReceive' => $amountToReceive,
                ]
            );

            return $this->view([
                'error' => $message,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/make/{base}/{quote}/{mode}",
     *     name="make_quick_trade",
     *     options={"expose"=true}
     * )
     * @Rest\RequestParam(name="amount", allowBlank=false, description="Amount to invest.")
     * @Rest\RequestParam(
     *     name="expected_count_to_receive",
     *     allowBlank=false,
     *     description="Expected assets count to receive."
     * )
     */
    public function makeTrade(Market $market, string $mode, ParamFetcherInterface $request): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $this->denyAccessUnlessGranted(DisabledServicesVoter::NEW_TRADES);
        $this->denyAccessUnlessGranted(DisabledServicesVoter::TRADING);

        $user = $this->getCurrentUser();

        if (!$this->isGranted('all-orders-enabled', $market)) {
            /** @var Token $token */
            $token = $market->getQuote();

            return $this->view(
                [
                    'message' => 'token.not_deployed_response',
                    'notified' => $this->deployNotificationManager->alreadyNotified($user, $token),
                ],
                Response::HTTP_OK
            );
        }

        $this->checkMarket($market);

        $lockBalance = $this->lockFactory->createLock(LockFactory::LOCK_BALANCE.$user->getId());

        $lockOrder = $this->lockFactory->createLock(
            LockFactory::LOCK_ORDER.$user->getId(),
            (int)$this->getParameter('order_delay'),
            false
        );

        if (!$lockBalance->acquire() || !$lockOrder->acquire()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $amount = (string)$request->get('amount');
            $expectedAmount = (string)$request->get('expected_count_to_receive');

            $this->validateProperties(null, [$amount, $expectedAmount]);

            $tradeResult = null;
            $type = null;

            if (!$this->isGranted('trades-enabled', $market)) {
                return $this->view(['error' => true, 'type' => 'action'], Response::HTTP_OK);
            }

            if (self::BUY === $mode) {
                if (!$this->isGranted('make-donation', $market)) {
                    return $this->view(['error' => true, 'type' => 'donation'], Response::HTTP_OK);
                }

                $quote = $market->getQuote();

                if ($quote instanceof Token) {
                    // Buy (Donation) in token market
                    $donation = $this->donationHandler->makeDonation(
                        $market,
                        $amount,
                        $expectedAmount,
                        $user
                    );

                    $type = $donation->getType();

                    /** @psalm-suppress TooManyArguments */
                    $this->eventDispatcher->dispatch(new DonationEvent($donation), TokenEvents::DONATION);

                    $tradeResult = new TradeResult(TradeResult::SUCCESS, $this->translator);
                } else {
                    // Buy in coin market
                    $tradeResult = $this->quickTrader->makeBuy(
                        $user,
                        $market,
                        $amount,
                        $expectedAmount
                    );

                    $type = self::TYPE_DIRECT_BUY;
                }
            } elseif (self::SELL === $mode) {
                if (!$this->isGranted('sell-order', $market)) {
                    return $this->view(['error' => true, 'type' => 'action'], Response::HTTP_OK);
                }

                $tradeResult = $this->quickTrader->makeSell(
                    $user,
                    $market,
                    $amount,
                    $expectedAmount
                );

                $type = self::TYPE_DIRECT_SELL;
            }

            $lockBalance->release();

            if (!$tradeResult) {
                throw QuickTradeException::invalidMode();
            }

            if (TradeResult::SUCCESS !== $tradeResult->getResult()) {
                throw new ApiBadRequestException($tradeResult->getMessage());
            }

            $this->logger->info($type, [
                'mode' => $mode,
                'base' => $market->getBase()->getName(),
                'quote' => $market->getQuote()->getName(),
                'amount' => $amount,
                'price' => $expectedAmount,
            ]);

            return $this->view(null, Response::HTTP_OK);
        } catch (\Throwable $ex) {
            $lockBalance->release();

            if ($response = $this->handleException($ex)) {
                return $response;
            }

            $message = $ex->getMessage();

            $this->logger->error(
                '[make_quick_trade] Failed to make trade.',
                [
                    'message' => $message,
                    'code' => $ex->getCode(),
                    'mode' => $mode,
                    'market' => $market,
                ]
            );

            return $this->view([
                'error' => $message,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getCurrentUser(): User
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    public function checkMarket(Market $market): void
    {
        $base = $market->getBase();

        $validBase = $base instanceof Crypto
            ? $base->isTradable()
            : false;

        $sameTradables = $base->getSymbol() === $market->getQuote()->getSymbol();

        if (!$validBase || $sameTradables) {
            throw QuickTradeException::invalidCurrency();
        }
    }

    private function handleException(\Throwable $ex): ?View
    {
        if ($ex instanceof QuickTradeException) {
            $key = $ex->getKey();
            $message = $this->translator->trans($key, $ex->getContext());

            return $this->view([
                'message' => $message,
                'availabilityChanged' => QuickTradeException::AVAILABILITY_CHANGED_KEY === $key,
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($ex instanceof CryptoCalculatorException) {
            $key = $ex->getKey();
            $message = $this->translator->trans($key, $ex->getContext());

            return $this->view([
                'message' => $message,
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->logger->error('[quick_trade] Error: ' . $ex->getMessage());

        if ($ex instanceof FetchException) {
            return $this->view([
                'message' => $this->translator->trans('toasted.error.external'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($ex instanceof ApiBadRequestException) {
            return $this->view(['message' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }
}
