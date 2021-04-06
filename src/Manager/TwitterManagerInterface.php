<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Exception\InvalidTwitterTokenException;

interface TwitterManagerInterface
{
    /**
     * @throws InvalidTwitterTokenException
     * @throws \Throwable
     */
    public function sendTweet(User $user, string $message): self;

    /**
     * @throws InvalidTwitterTokenException
     * @throws \Throwable
     */
    public function retweet(User $user, string $tweetId): self;
}
