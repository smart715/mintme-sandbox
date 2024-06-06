<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\BlockedUser;
use App\Entity\User;

interface BlockedUserManagerInterface
{
    public function blockUser(User $user, User $userToBlock, bool $removeActions = false): void;
    
    public function unblockUser(BlockedUser $blockedUser): void;

    public function findByBlockedUserAndOwner(User $user, User $blockedUser): ?BlockedUser;
}
