<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\PendingWithdraw;
use App\Entity\User;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;

interface PendingManagerInterface
{
    public function create(User $user, Address $address, Amount $amount, Crypto $crypto): PendingWithdraw;
}
