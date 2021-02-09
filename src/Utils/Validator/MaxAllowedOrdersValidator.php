<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;

class MaxAllowedOrdersValidator implements ValidatorInterface
{
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

        $userPendingOrders = $this->marketHandler->getPendingOrdersByUser(
            $this->user,
            $this->marketFactory->createUserRelated($this->user),
            0,
            $this->maxAllowedOrders
        );

        return count($userPendingOrders) !== $this->maxAllowedOrders;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
