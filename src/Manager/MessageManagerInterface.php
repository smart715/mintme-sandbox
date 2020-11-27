<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Message\Thread;
use App\Entity\User;

interface MessageManagerInterface
{
    public function sendMessage(Thread $thread, User $sender, string $body): void;
    public function getMessages(Thread $thread, int $limit, int $offset): array;
    public function getNewMessages(Thread $thread, int $lastMessageId): array;
    public function setRead(Thread $thread, User $participant): void;
    public function getUnreadCount(User $user): int;
}
