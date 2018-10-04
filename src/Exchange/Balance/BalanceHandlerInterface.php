<?php

namespace App\Exchange\Balance;

use App\Entity\User;

interface BalanceHandlerInterface
{
    public function deposit(User $user, string $assetName, string $balance): void;
    
    public function withdraw(User $user, string $assetName, string $balance): void;
}
