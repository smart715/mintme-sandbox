<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;

class MaxAllowedOrdersValidator implements ValidatorInterface
{
    private const MAX_PENDING_ORDERS = 100;

    private int $maxAllowedOrders;
    private string $message;

    private User $user;
    private MarketHandlerInterface $marketHandler;
    private MarketFactoryInterface $marketFactory;

    public function __construct(
        int $maxAllowedOrders,
        User $user,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory
    ) {
        $this->maxAllowedOrders = $maxAllowedOrders;
        $this->user = $user;
        $this->marketHandler = $marketHandler;
        $this->marketFactory = $marketFactory;
    }

    public function validate(): bool
    {
        $this->message = 'You can have maximum of ' . $this->maxAllowedOrders . ' active orders';

        $leftRequests = ceil($this->maxAllowedOrders / self::MAX_PENDING_ORDERS);
        $offset = 0;
        $pendingOrdersCount = 0;

        do {
            $pendingOrders = $this->marketHandler->getPendingOrdersByUser(
                $this->user,
                $this->marketFactory->createUserRelated($this->user),
                $offset,
                self::MAX_PENDING_ORDERS
            );

            $pendingOrdersCount += count($pendingOrders);
            $leftRequests--;
            $offset += self::MAX_PENDING_ORDERS;
        } while ($pendingOrdersCount >= self::MAX_PENDING_ORDERS && $leftRequests);

        return $pendingOrdersCount < $this->maxAllowedOrders;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
