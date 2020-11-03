<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Message\Thread;
use App\Entity\Token\Token;
use App\Entity\User;

interface ThreadManagerInterface
{
    public function find(int $id): ?Thread;
    public function firstOrNewDMThread(Token $token, User $trader): Thread;
    public function traderThreads(User $trader): Array;
}
