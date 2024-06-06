<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\UserTokenFollowException;

interface UserTokenFollowManagerInterface
{
    /**
     * @throws UserTokenFollowException
     */
    public function manualFollow(Token $token, User $user): void;

    /**
     * @throws UserTokenFollowException
     */
    public function manualUnfollow(Token $token, User $user): void;

    /**
     * @throws UserTokenFollowException
     */
    public function autoFollow(Token $token, User $user): void;
    public function getFollowStatus(Token $token, User $user): string;

    /**
     * @return User[]
     */
    public function getFollowers(Token $token): array;

    /**
     * @return Token[]
     */
    public function getFollowedTokens(User $user): array;

    public function isFollower(User $user, Token $token): bool;
}
