<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\DonationEvent;
use App\Events\TokenEvents;
use App\Exception\ApiBadRequestException;
use App\Exchange\CheckTradeResult;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\Donation\DonationHandler;
use App\Exchange\Donation\DonationHandlerInterface;
use App\Exchange\ExchangerInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TradeResult;
use App\Logger\DonationLogger;
use App\Utils\LockFactory;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Exchange\FixedExchange;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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
    private MoneyWrapperInterface $moneyWrapper;
    private ExchangerInterface $exchanger;

    private LockFactory $lockFactory;

    private QuickTradeConfig $config;

    public function __construct(
        DonationHandlerInterface $donationHandler,
        MarketHandlerInterface $marketHandler,
        DonationLogger $logger,
        LockFactory $lockFactory,
        EventDispatcherInterface $eventDispatcher,
        MoneyWrapperInterface $moneyWrapper,
        ExchangerInterface $exchanger,
        QuickTradeConfig $config
    ) {
        $this->donationHandler = $donationHandler;
        $this->marketHandler = $marketHandler;
        $this->logger = $logger;
        $this->lockFactory = $lockFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->moneyWrapper = $moneyWrapper;
        $this->exchanger = $exchanger;
        $this->config = $config;
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

                $tokensWorth = $this->donationHandler->getTokensWorth($checkDonationResult->getTokensWorth(), $currency);
                $sellOrdersSummary = $this->marketHandler->getSellOrdersSummary($market)->getBaseAmount();
                $sellOrdersSummary = $this->donationHandler->getTokensWorth($sellOrdersSummary, $currency);

                return $this->view([
                    'amountToReceive' => $checkDonationResult->getExpectedTokens(),
                    'worth' => $tokensWorth,
                    'ordersSummary' => $sellOrdersSummary,
                ]);
            } elseif (self::SELL === $mode) {
                $checkSell = $this->checkSell($market, $amount);

                $buyOrdersSummary = $this->marketHandler->getBuyOrdersSummary($market)->getQuoteAmount();

                return $this->view([
                    'amountToReceive' => $checkSell->getExpectedAmount(),
                    'worth' => $checkSell->getWorth() ?? '0',
                    'ordersSummary' => $buyOrdersSummary,
                ]);
            } else {
                throw new ApiBadRequestException('Trade mode is invalid ' . $mode);
            }
        } catch (ApiBadRequestException $ex) {
            return $this->view([
                'message' => $ex->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $ex) {
            $message = $ex->getMessage();

            $this->logger->error(
                '[check_donation] Failed to check donation.',
                [
                    'message' => $message,
                    'code' => $ex->getCode(),
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

        if (!$this->isGranted('make-donation')) {
            return $this->view(['error' => true, 'type' => 'donation'], Response::HTTP_OK);
        }

        $lock = $this->lockFactory->createLock(LockFactory::LOCK_BALANCE.$user->getId());

        if (!$lock->acquire()) {
            throw $this->createAccessDeniedException();
        }

        try {
            if (self::BUY === $mode) {
                $sellOrdersSummary = $this->marketHandler->getSellOrdersSummary($market)->getBaseAmount();

                $donation = $this->donationHandler->makeDonation(
                    $market,
                    $request->get('currency'),
                    (string)$request->get('amount'),
                    (string)$request->get('expected_count_to_receive'),
                    $user,
                    $sellOrdersSummary
                );

                /** @psalm-suppress TooManyArguments */
                $this->eventDispatcher->dispatch(new DonationEvent($donation), TokenEvents::DONATION);
            } elseif (self::SELL === $mode) {
                $amount = (string)$request->get('amount');
                $expectedAmount = (string)$request->get('expected_count_to_receive');

                $tradeResult = $this->makeSell(
                    $user,
                    $market,
                    $amount,
                    $expectedAmount
                );

                if (TradeResult::SUCCESS !== $tradeResult->getResult()) {
                    throw new ApiBadRequestException($tradeResult->getMessage());
                }
            } else {
                throw new ApiBadRequestException('Trade mode is invalid ' . $mode);
            }

            return $this->view(null, Response::HTTP_OK);
        } catch (ApiBadRequestException $ex) {
            $lock->release();

            return $this->view([
                'message' => $ex->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $ex) {
            $message = $ex->getMessage();

            $this->logger->error(
                '[make_donation] Failed to make donation.',
                [
                    'message' => $message,
                    'code' => $ex->getCode(),
                    'market' => $market,
                ]
            );

            $lock->release();

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

    private function checkSell(Market $market, string $amount): CheckTradeResult
    {
        $quote = $market->getQuote();

        $baseSymbol = $market->getBase()->getSymbol();

        $quoteSymbol = $quote instanceof Token ?
            Symbols::TOK :
            $quote->getSymbol();

        $amountToReceive = $this->moneyWrapper->parse('0', $baseSymbol);

        $quoteLeft = $this->moneyWrapper->parse($amount, $quoteSymbol);

        $fee = $this->config->getFee();

        $offset = 0;
        $limit = 100;

        do {
            $pendingBuyOrders = $this->marketHandler->getPendingBuyOrders($market, $offset, $limit);
            $shouldContinue = count($pendingBuyOrders) >= $limit;
            $offset += $limit;

            foreach ($pendingBuyOrders as $bid) {
                if ($quoteLeft->isZero()) {
                    $shouldContinue = false;

                    break;
                }

                $orderAmountWithFee = $bid->getAmount()->subtract(
                    $bid->getAmount()->multiply($fee)
                );

                if ($quoteLeft->greaterThanOrEqual($bid->getAmount())) {
                    $baseWorth = $this->moneyWrapper->convertByRatio(
                        $orderAmountWithFee,
                        $bid->getPrice()->getCurrency()->getCode(),
                        $this->moneyWrapper->format($bid->getPrice())
                    );

                    $amountToReceive = $amountToReceive->add($baseWorth);
                    $quoteLeft = $quoteLeft->subtract($bid->getAmount());
                } else {
                    $quoteLeftWithFee = $quoteLeft->subtract($quoteLeft->multiply($fee));

                    $baseWorth = $this->moneyWrapper->convertByRatio(
                        $quoteLeftWithFee,
                        $bid->getPrice()->getCurrency()->getCode(),
                        $this->moneyWrapper->format($bid->getPrice())
                    );

                    $amountToReceive = $amountToReceive->add($baseWorth);
                    $quoteLeft = $quoteLeft->subtract($quoteLeft);
                }
            }
        } while ($shouldContinue);

        return new CheckTradeResult($amountToReceive);
    }

    private function makeSell(User $user, Market $market, string $amount, string $expectedAmount): TradeResult
    {
        $quote = $market->getQuote();

        $baseSymbol = $market->getBase()->getSymbol();

        $quoteSymbol = $quote instanceof Token ?
            Symbols::TOK :
            $quote->getSymbol();

        $expectedAmount = $this->moneyWrapper->parse($expectedAmount, $baseSymbol);

        $checkResult = $this->checkSell($market, $amount);

        if (!$expectedAmount->equals($checkResult->getExpectedAmount())) {
            throw new ApiBadRequestException('Token availability changed.');
        }

        $amount = $this->moneyWrapper->parse($amount, $quoteSymbol);

        $buyOrdersSummary = $this->marketHandler->getBuyOrdersSummary($market)->getQuoteAmount();
        $buyOrdersSummary = $this->moneyWrapper->parse($buyOrdersSummary, $quoteSymbol);

        if ($amount->greaterThan($buyOrdersSummary)) {
            throw new ApiBadRequestException('Exceeding buy orders');
        }

        return $this->exchanger->executeOrder(
            $user,
            $market,
            $this->moneyWrapper->format($amount),
            $this->moneyWrapper->format($expectedAmount),
            Order::SELL_SIDE,
            $this->config->getFee()
        );
    }
}
