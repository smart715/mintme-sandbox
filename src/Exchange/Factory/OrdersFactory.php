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
    public const STEP = 0.01738;

    private TraderInterface $trader;

    private MarketFactoryInterface $marketFactory;

    private CryptoManagerInterface $cryptoManager;

    private ParameterBagInterface $bag;

    private UserActionLogger $userLogger;

    private MoneyWrapperInterface $moneyWrapper;

    private TokenManagerInterface $tokenManager;

    private Money $currentStep;

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
        $this->currentStep = $this->moneyWrapper->parse((string)self::INIT_TOKEN_PRICE, MoneyWrapper::TOK_SYMBOL);
    }

    public function createInitOrders(Token $token): void
    {
        $user = $token->getOwner();

        if (!$user) {
            throw new \LogicException();
        }

        $market = $this->getMintmeMarketForToken($token);
        $amount = $this->getStepAmount();
        $fee = $this->moneyWrapper->parse((string)$this->bag->get('maker_fee_rate'), MoneyWrapper::TOK_SYMBOL);

        $price = null;

        for ($i = self::INIT_TOKENS_STEP_AMOUNT; $i <= self::INIT_TOKENS_AMOUNT; $i += self::INIT_TOKENS_STEP_AMOUNT) {
            $price = $this->getStepPrice($price);

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

            $this->trader->placeOrder($order, false);
        }
    }

    private function getStepPrice(?Money $currentPrice): Money
    {
        $amount = (string) BigDecimal::of(self::INIT_TOKEN_PRICE)->dividedBy(
            '1',
            4,
            RoundingMode::HALF_DOWN
        );

        $price = $this->moneyWrapper->parse($amount, MoneyWrapper::TOK_SYMBOL);

        if ($currentPrice) {
            $this->currentStep = $this->currentStep->subtract($this->currentStep->multiply(self::STEP));

            return $currentPrice->add($this->currentStep);
        } else {
            return $price;
        }
    }

    private function getStepAmount(): Money
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
