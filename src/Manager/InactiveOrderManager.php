<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\InactiveOrder;
use App\Entity\User;
use App\Exchange\Market;
use App\Repository\InactiveOrderRepository;

/** @codeCoverageIgnore */
class InactiveOrderManager implements InactiveOrderManagerInterface
{
    private InactiveOrderRepository $inactiveOrderRepository;

    public function __construct(InactiveOrderRepository $inactiveOrderRepository)
    {
        $this->inactiveOrderRepository = $inactiveOrderRepository;
    }

    public function exists(int $userId, int $orderId): bool
    {
        return (bool)$this->inactiveOrderRepository->findOneBy([
            'user' => $userId,
            'orderId' => $orderId,
        ]);
    }

    public static function make(User $user, Market $market, int $orderId): InactiveOrder
    {
        [$base, $quote] = [$market->getBase(), $market->getQuote()];

        if (!$base instanceof Crypto || !$quote instanceof Crypto) {
            throw new \InvalidArgumentException('Market must have base and quote crypto');
        }

        return new InactiveOrder($user, $base, $quote, $orderId);
    }
}
