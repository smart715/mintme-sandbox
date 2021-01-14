<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Repository\DonationRepository;

class DonationManager implements DonationManagerInterface
{
    private DonationRepository $repository;

    public function __construct(DonationRepository $donationRepository)
    {
        $this->repository = $donationRepository;
    }

    /** {@inheritDoc} */
    public function getAllUserRelated(User $user): array
    {
        return $this->repository->findAllUserRelated($user);
    }
}
