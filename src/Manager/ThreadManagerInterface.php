<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Message\Thread;
use App\Entity\Token\Token;
use App\Entity\User;

interface ThreadManagerInterface
{
    public function find(int $id): ?Thread;
    public function delete(int $id): void;
    public function firstOrNewDMThread(Token $token, User $trader): Thread;
    public function traderThreads(User $trader): Array;
    public function toggleBlockUser(array $threadMetadata, User $participant): void;
    public function toggleHiddenThread(array $threadMetadata, User $participant): void;
    public function showHiddenThread(array $threadMetadata, User $participant): void;
    public function areAllThreadsHidden(array $threadMetadata): bool;
}
