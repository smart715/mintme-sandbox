<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TokenInitOrder;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\BalanceViewFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use LogicException;
use Money\Money;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OrdersFactory implements OrdersFactoryInterface
{
    public const INIT_TOKENS_AMOUNT = 1500000;
    public const INIT_TOKEN_PRICE = 0.1;
    public const STEP = 0.01691495;
    public const MINIMUM_PRICE_DIFFERENCE = 0.0001;

    private TraderInterface $trader;

    private MarketFactoryInterface $marketFactory;

    private CryptoManagerInterface $cryptoManager;

    private ParameterBagInterface $bag;

    private MoneyWrapperInterface $moneyWrapper;

    private Money $currentStep;
    private LimitOrderConfig $limitOrderConfig;
    private BalanceHandlerInterface $balanceHandler;
    private BalanceViewFactoryInterface $balanceViewFactory;

    public function __construct(
        TraderInterface $trader,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        ParameterBagInterface $bag,
        MoneyWrapperInterface $moneyWrapper,
        LimitOrderConfig $limitOrderConfig,
        BalanceHandlerInterface $balanceHandler,
        BalanceViewFactoryInterface $balanceViewFactory
    ) {
        $this->trader = $trader;
        $this->marketFactory = $marketFactory;
        $this->cryptoManager = $cryptoManager;
        $this->bag = $bag;
        $this->moneyWrapper = $moneyWrapper;
        $this->currentStep = $this->moneyWrapper->parse((string)self::INIT_TOKEN_PRICE, Symbols::TOK);
        $this->limitOrderConfig = $limitOrderConfig;
        $this->balanceHandler = $balanceHandler;
        $this->balanceViewFactory = $balanceViewFactory;
    }

    public function createInitOrders(
        Token $token,
        string $initTokenPrice,
        ?string $priceGrowth,
        string $tokensForSale
    ): void {
        $config = $this->bag->get('initial_sell_order_config');
        $totalOrders = $config['totalOrders'];
        $minTokensAmount = $this->moneyWrapper->parse((string)$config['minTokensAmount'], Symbols::TOK);

        $user = $token->getOwner();

        if (!$user) {
            throw new LogicException();
        }

        if ($this->moneyWrapper->parse($initTokenPrice, Symbols::TOK)->lessThan($minTokensAmount)
            || !preg_match('/^\d+(\.\d+)?$/', (string)$initTokenPrice)
        ) {
            throw new ApiBadRequestException('Invalid starting price');
        }

        if ($this->exceedAvailableReleased($user, $token, $tokensForSale)) {
            throw new ApiBadRequestException('Insufficient balance');
        }

        $market = $this->getMintmeMarketForToken($token);
        $amount = $this->getInitTokensStepAmount($tokensForSale);
        $userFee = $user->getTradingFee() ?? $this->limitOrderConfig->getFeeTokenRate();
        $fee = $this->moneyWrapper->parse($userFee, Symbols::TOK);
        $price = null;

        for ($i = 1; $i <= $totalOrders; $i++) {
            if (1 === $i && null !== $priceGrowth) {
                $price = $this->moneyWrapper->parse($initTokenPrice, Symbols::TOK);

                $this->trader->placeOrder(
                    $this->getNewOrder($user, $market, $amount, $price, $fee),
                    false,
                    true
                );

                continue;
            }

            $price = null !== $priceGrowth ?
                $this->getStepPrice($initTokenPrice, $priceGrowth, $i, $price) :
                $this->getStepPriceForCommand($price);

            if (null !== $priceGrowth) {
                $this->trader->placeOrder(
                    $this->getNewOrder($user, $market, $amount, $price, $fee),
                    false,
                    true
                );
            }
        }
    }

    private function getInitTokensStepAmount(string $tokensForSale): Money
    {
        $amount = (string) BigDecimal::of($tokensForSale)->dividedBy(
            '100',
            4,
            RoundingMode::HALF_DOWN
        );

        return $this->moneyWrapper->parse($amount, Symbols::TOK);
    }

    private function getMintmeMarketForToken(Token $token): Market
    {
        /** @var Crypto $mintmeCrypto */
        $mintmeCrypto = $this->cryptoManager->findBySymbol(Symbols::WEB);

        return $this->marketFactory->create($mintmeCrypto, $token);
    }

    private function getStepPrice(string $initTokenPrice, string $priceGrowth, int $nOrder, Money $previousPrice): Money
    {
        $calculatedPrice = $this->calculatePrice($initTokenPrice, $priceGrowth, $nOrder);

        return $this->isGreaterThanMinimumAllowedDifference($calculatedPrice, $previousPrice) ?
            $calculatedPrice:
            $this->calculateMinimumAllowedPrice($previousPrice);
    }

    private function getStepPriceForCommand(?Money $currentPrice): Money
    {
        $amount = (string) BigDecimal::of(self::INIT_TOKEN_PRICE)->dividedBy(
            '1',
            4,
            RoundingMode::HALF_DOWN
        );

        if (!$currentPrice) {
            return $this->moneyWrapper->parse($amount, Symbols::TOK);
        }

        $this->currentStep = $this->currentStep->subtract($this->currentStep->multiply(self::STEP));

        return $currentPrice->add($currentPrice->multiply($this->moneyWrapper->format($this->currentStep)));
    }

    private function getNewOrder(User $user, Market $market, Money $amount, Money $price, Money $fee): Order
    {
        return new Order(
            null,
            $user,
            null,
            $market,
            $amount,
            Order::SELL_SIDE,
            $price,
            Order::PENDING_STATUS,
            $fee,
            null,
            null,
            $user->getReferencer() ? (int)$user->getReferencer()->getId() : 0
        );
    }
    public function removeTokenInitOrders(User $user, Token $token, TokenInitOrder $order): void
    {
        $order = Order::createCancelOrder(
            $order->getOrderId(),
            $user,
            $this->getMintmeMarketForToken($token)
        );

        $tradeResult = $this->trader->cancelOrder($order);

        if ($tradeResult::ORDER_NOT_FOUND === $tradeResult->getResult()) {
            throw new ApiBadRequestException('Invalid request');
        }

        if ($tradeResult::USER_NOT_MATCH === $tradeResult->getResult()) {
            throw new ApiBadRequestException('Access denied for cancel order');
        }
    }

    private function calculatePrice(string $initTokenPrice, string $priceGrowth, int $nOrder): Money
    {
        $price = (string)BigDecimal::of($initTokenPrice)->multipliedBy(
            pow(
                BigDecimal::of($priceGrowth)->dividedBy(100, 4)->plus(1)->toFloat(),
                log($nOrder, 2)
            ),
        )->toScale(4, RoundingMode::HALF_UP);

        return $this->moneyWrapper->parse($price, Symbols::TOK);
    }

    private function calculateMinimumAllowedPrice(Money $previousPrice): Money
    {
        $minimumDifference = $this->moneyWrapper->parse((string)self::MINIMUM_PRICE_DIFFERENCE, Symbols::TOK);

        return $previousPrice->add($minimumDifference);
    }

    private function isGreaterThanMinimumAllowedDifference(Money $currentPrice, Money $previousPrice): bool
    {
        $minimumDifference = $this->moneyWrapper->parse((string)self::MINIMUM_PRICE_DIFFERENCE, Symbols::TOK);

        return $currentPrice->subtract($minimumDifference)->greaterThanOrEqual($previousPrice);
    }

    private function exceedAvailableReleased(
        User $user,
        Token $token,
        string $amount
    ): bool {
        $balanceViewer = $this->balanceViewFactory->create(
            [$token],
            $this->balanceHandler->balances($user, [$token]),
            $user
        )[$token->getSymbol()];

        return $this->moneyWrapper
            ->parse($amount, Symbols::TOK)
            ->greaterThan($balanceViewer->getAvailable());
    }
}
