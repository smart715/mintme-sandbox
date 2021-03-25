<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
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
    public const INIT_TOKENS_STEP_AMOUNT = 15000;
    public const INIT_TOKEN_PRICE = 0.1;
    public const STEP = 0.01691495;

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
        $this->currentStep = $this->moneyWrapper->parse((string)self::INIT_TOKEN_PRICE, Symbols::TOK);
    }

    public function createInitOrders(Token $token): void
    {
        $user = $token->getOwner();

        if (!$user) {
            throw new LogicException();
        }

        $market = $this->getMintmeMarketForToken($token);
        $amount = $this->getStepAmount();
        $fee = $this->moneyWrapper->parse((string)$this->bag->get('maker_fee_rate'), Symbols::TOK);

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

        if (!$currentPrice) {
            return $this->moneyWrapper->parse($amount, Symbols::TOK);
        }

        $this->currentStep = $this->currentStep->subtract($this->currentStep->multiply(self::STEP));

        return $currentPrice->add($currentPrice->multiply($this->moneyWrapper->format($this->currentStep)));
    }

    private function getStepAmount(): Money
    {
        $amount = (string) BigDecimal::of(self::INIT_TOKENS_STEP_AMOUNT)->dividedBy(
            '1',
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
}
