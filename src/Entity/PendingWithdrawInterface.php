<?php declare(strict_types = 1);

namespace App\Entity;

use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;

interface PendingWithdrawInterface
{
    public function getAddress(): Address;

    public function getAmount(): Amount;

    public function getHash(): string;

    public function getUser(): User;

    public function getSymbol(): string;
}
