<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Money;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;

class OrdersFactory implements OrdersFactoryInterface
{
    public const INIT_TOKENS_AMOUNT = 1500000;
    public const INIT_TOKENS_STEP_AMOUNT = 15000;
    public const INIT_TOKEN_PRICE = 0.1;

    private TraderInterface $trader;

    private MarketFactoryInterface $marketFactory;

    private CryptoManagerInterface $cryptoManager;

    private ParameterBagInterface $bag;

    private UserActionLogger $userLogger;

    private MoneyWrapperInterface $moneyWrapper;

    private TokenManagerInterface $tokenManager;

    public function __construct(
        TraderInterface $trader,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        ParameterBagInterface $bag,
        UserActionLogger $userLogger,
        MoneyWrapperInterface $moneyWrapper,
        TokenManagerInterface $tokenManager
    ) {
        $this->trader = $trader;
        $this->marketFactory = $marketFactory;
        $this->cryptoManager = $cryptoManager;
        $this->bag = $bag;
        $this->userLogger = $userLogger;
        $this->moneyWrapper = $moneyWrapper;
        $this->tokenManager = $tokenManager;
    }

    public function createInitOrders(User $user): void
    {
        $token = $this->tokenManager->getOwnToken();
        $market = $this->getMintmeMarketForToken($token);
        $amount = $this->getStepAmount($token);
        $fee = $this->moneyWrapper->parse((string)$this->bag->get('maker_fee_rate'), MoneyWrapper::TOK_SYMBOL);

        /* @TODO Check if token has enough amount */

        for ($i = 0; $i < self::INIT_TOKENS_AMOUNT; $i = $i + self::INIT_TOKENS_STEP_AMOUNT) {
            $price = $this->getStepPrice(1);

            $order = new Order(
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

            $this->trader->placeOrder($order);
        }

    }

    private function getStepPrice($currentPrice): Money
    {
        $amount = (string) BigDecimal::of(1)->dividedBy(
            '1',
            4,
            RoundingMode::HALF_DOWN
        );

        return $this->moneyWrapper->parse($amount, MoneyWrapper::TOK_SYMBOL);
    }

    private function getStepAmount(Token $token): Money
    {
        $amount = (string) BigDecimal::of(self::INIT_TOKENS_STEP_AMOUNT)->dividedBy(
            '1',
            4,
            RoundingMode::HALF_DOWN
        );

        return $this->moneyWrapper->parse($amount, MoneyWrapper::TOK_SYMBOL);
    }

    private function getMintmeMarketForToken(Token $token): Market
    {
        /** @var Crypto $mintmeCrypto */
        $mintmeCrypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);

        return $this->marketFactory->create($mintmeCrypto, $token);
    }
}
