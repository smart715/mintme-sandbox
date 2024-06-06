<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Entity\Token\Token;
use App\Events\Activity\ActivityEventInterface;
use App\Events\OrderEventInterface;
use App\Exchange\Order;

class OrderEventActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof OrderEventInterface) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        $order = $event->getOrder();
        $market = $order->getMarket();
        $token = $market->getQuote();
        $base = $market->getBase();

        if (!$token instanceof Token) {
            throw new \AssertionError('Unsupported market type');
        }

        $price = $order->getPrice();
        $amount = $order->getAmount();
        $amount = $this->moneyWrapper->format($amount);
        $totalPrice = $price->multiply($amount);

        $buyer = Order::SELL_SIDE === $order->getSide()
            ? $order->getMaker()
            : $order->getTaker();

        if (!$buyer) {
            throw new \AssertionError('Buyer not found');
        }

        return new Activity($event->getType(), [
            'buyerIconUrl' => $this->activityHelper->profileIcon($buyer),
            'buyerUrl' => $this->router->generate('profile-view', [
                'nickname' => $buyer->getProfile()->getNickname(),
            ]),
            'buyer' => $this->activityHelper->truncate($buyer->getProfile()->getNickname(), 12),
            'tokenIconUrl' => $this->activityHelper->tokenIcon($token),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $token->getName()]),
            'token' => $this->activityHelper->truncate($token->getName(), 12),
            'amount' => $this->moneyWrapper->format($totalPrice, false),
            'symbol' => $this->activityHelper->rebrand($base->getSymbol()),
            'tradeIconUrl' => $this->activityHelper->tradeIcon($base->getSymbol()),
            'id' => $event->getOrder()->getId(),
        ]);
    }
}
