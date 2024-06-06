<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\TopHolder;
use App\Entity\User;

interface TopHolderManagerInterface
{
    public function updateTopHolders(Token $token): void;

    public function shouldUpdateTopHolders(User $user, Token $token): bool;

    public function getOwnTopHolders(): array;

    public function getTopHolderByUserAndToken(User $user, Token $token): ?TopHolder;
}
