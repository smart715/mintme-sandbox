<?php

namespace App\Exchange\Balance;

use App\Entity\Token;
use App\Entity\User;

interface BalanceHandlerInterface
{
    public function deposit(User $user, Token $token, string $amount): void;
    
    public function withdraw(User $user, Token $token, string $amount): void;
}
