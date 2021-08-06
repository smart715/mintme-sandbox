<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\DonationEvent;
use App\Events\TokenEvents;
use App\Exception\ApiBadRequestException;
use App\Exception\QuickTradeException;
use App\Exchange\Donation\DonationHandlerInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\QuickTraderInterface;
use App\Exchange\Trade\TradeResult;
use App\Logger\DonationLogger;
use App\Utils\LockFactory;
use App\Utils\Symbols;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/quick-trade")
 */
class QuickTradeController extends AbstractFOSRestController
{
    public const BUY = 'buy';
    public const SELL = 'sell';

    protected DonationHandlerInterface $donationHandler;
    protected MarketHandlerInterface $marketHandler;
    protected DonationLogger $logger;
    protected EventDispatcherInterface $eventDispatcher;

    private LockFactory $lockFactory;
    private TranslatorInterface $translator;
    private QuickTraderInterface $quickTrader;

    public function __construct(
        DonationHandlerInterface $donationHandler,
        MarketHandlerInterface $marketHandler,
        DonationLogger $logger,
        LockFactory $lockFactory,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        QuickTraderInterface $quickTrader
    ) {
        $this->donationHandler = $donationHandler;
        $this->marketHandler = $marketHandler;
        $this->logger = $logger;
        $this->lockFactory = $lockFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->quickTrader = $quickTrader;
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/check/{base}/{quote}/{mode}/{currency}/{amount}",
     *     name="check_quick_trade",
     *     options={"expose"=true},
     * )
     * @Rest\RequestParam(name="amount", allowBlank=false, description="Amount to invest.")
     * @Rest\RequestParam(
     *     name="currency",
     *     allowBlank=false,
     *     description="Selected currency to trade."
     * )
     * @Rest\RequestParam(
     *     name="mode",
     *     allowBlank=false,
     *     requirements="^(sell|buy)$"),
     *     description="Trade mode"
     */
    public function checkTrade(Market $market, string $mode, string $currency, string $amount): View
    {
        try {
            /** @var User|null $user */
            $user = $this->getUser();

            if (self::BUY === $mode) {
                $checkDonationResult = $this->donationHandler->checkDonation(
                    $market,
                    $currency,
                    $amount,
                    $user
                );

                $tokensWorth = $checkDonationResult->getTokensWorth();
                $sellOrdersSummary = $this->marketHandler->getSellOrdersSummary($market)->getBaseAmount();

                return $this->view([
                    'amountToReceive' => $checkDonationResult->getExpectedTokens(),
                    'worth' => $tokensWorth,
                    'ordersSummary' => $sellOrdersSummary,
                ]);
            }

            if (self::SELL === $mode) {
                $checkResult = $this->quickTrader->checkSell($market, $amount);

                $buyOrdersSummary = $this->marketHandler->getBuyOrdersSummary($market)->getQuoteAmount();

                return $this->view([
                    'amountToReceive' => $checkResult->getExpectedAmount(),
                    'worth' => $checkResult->getWorth() ?? '0',
                    'ordersSummary' => $buyOrdersSummary,
                ]);
            }

            throw QuickTradeException::invalidMode($mode);
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
                    'currency' => $currency,
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
     * @Rest\Post(
     *     "/make/{base}/{quote}/{mode}",
     *     name="make_quick_trade",
     *     options={"expose"=true}
     * )
     * @Rest\RequestParam(
     *     name="currency",
     *     allowBlank=false,
     *     description="Selected currency to trade."
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
        $this->denyAccessUnlessGranted('new-trades');
        $this->denyAccessUnlessGranted('trading');
        $user = $this->getCurrentUser();

        $lock = $this->lockFactory->createLock(LockFactory::LOCK_BALANCE.$user->getId());

        if (!$lock->acquire()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $amount = (string)$request->get('amount');
            $expectedAmount = (string)$request->get('expected_count_to_receive');

            if (self::BUY === $mode) {
                if (!$this->isGranted('make-donation')) {
                    return $this->view(['error' => true, 'type' => 'donation'], Response::HTTP_OK);
                }

                $sellOrdersSummary = $this->marketHandler->getSellOrdersSummary($market)->getBaseAmount();

                $donation = $this->donationHandler->makeDonation(
                    $market,
                    $request->get('currency'),
                    $amount,
                    $expectedAmount,
                    $user,
                    $sellOrdersSummary
                );

                /** @psalm-suppress TooManyArguments */
                $this->eventDispatcher->dispatch(new DonationEvent($donation), TokenEvents::DONATION);

                $lock->release();

                return $this->view(null, Response::HTTP_OK);
            }

            if (self::SELL === $mode) {
                if (!$this->isGranted('sell-order', $market)) {
                    return $this->view(['error' => true, 'type' => 'action'], Response::HTTP_OK);
                }

                $tradeResult = $this->quickTrader->makeSell(
                    $user,
                    $market,
                    $amount,
                    $expectedAmount
                );

                if (TradeResult::SUCCESS !== $tradeResult->getResult()) {
                    throw new ApiBadRequestException($tradeResult->getMessage());
                }

                $lock->release();

                return $this->view(null, Response::HTTP_OK);
            }

            throw QuickTradeException::invalidMode($mode);
        } catch (\Throwable $ex) {
            $lock->release();

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

    private function handleException(\Throwable $ex): ?View
    {
        if ($ex instanceof QuickTradeException) {
            $key = $ex->getKey();
            $message = $this->translator->trans($key, $ex->getContext());

            return $this->view([
                'message' => $message,
                'reload' => QuickTradeException::AVAILABILITY_CHANGED_KEY === $key,
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($ex instanceof ApiBadRequestException) {
            return $this->view(['message' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }
}
