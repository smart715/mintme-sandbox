<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\InactiveOrder;
use App\Entity\User;
use App\Exchange\Market;

interface InactiveOrderManagerInterface
{
    public function exists(int $userId, int $orderId): bool;

    public static function make(User $user, Market $market, int $orderId): InactiveOrder;
}
