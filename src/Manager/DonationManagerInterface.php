<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Donation;
use App\Entity\User;

interface DonationManagerInterface
{
    /**
     * @param User $user
     * @return Donation[]
     */
    public function getAllUserRelated(User $user): array;
}
