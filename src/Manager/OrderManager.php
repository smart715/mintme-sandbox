<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\TokenInitOrder;
use App\Entity\User;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;

class OrderManager implements OrderManagerInterface
{
    private const ORDERS_LIMIT = 100;
    private MarketFactoryInterface $marketFactory;
    private MarketHandlerInterface $marketHandler;
    private ExchangerInterface $exchanger;
    private EntityManagerInterface $em;
    private const MEMORY_ERROR = 'Maximum function nesting level of \'256\' reached, aborting!';

    public function __construct(
        MarketFactoryInterface $marketFactory,
        MarketHandlerInterface $marketHandler,
        ExchangerInterface $exchanger,
        EntityManagerInterface $em
    ) {
        $this->marketFactory = $marketFactory;
        $this->marketHandler = $marketHandler;
        $this->exchanger = $exchanger;
        $this->em = $em;
    }

    public function deleteOrdersByUser(User $user): void
    {
        $orders = [];

        do {
            try {
                $orders = $this->marketHandler->getPendingOrdersByUser(
                    $user,
                    $this->marketFactory->createUserRelated($user),
                    0,
                    self::ORDERS_LIMIT
                );

                foreach ($orders as $order) {
                    $this->exchanger->cancelOrder($order->getMarket(), $order);
                }
            } catch (\Throwable $exception) {
                if (self::MEMORY_ERROR === $exception->getMessage()) {
                    continue;
                }
            }
        } while (count($orders) >= self::ORDERS_LIMIT);

        $token = $user->getProfile()->getFirstToken();

        if ($token) {
            $initialOrders = $this->em->getRepository(TokenInitOrder::class)->findBy(['user' => $user]);

            /** @var TokenInitOrder $order */
            foreach ($initialOrders as $order) {
                $this->em->remove($order);
                $this->em->flush();
            }
        }
    }
}
