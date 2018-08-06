<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;

class ProfileManager implements ProfileManagerInterface
{
    /** @var ProfileRepository */
    private $profileRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->profileRepository = $entityManager->getRepository(Profile::class);
    }

    public function getProfile(UserInterface $user): ?Profile
    {
        return $this->profileRepository->getProfileByUser($user);
    }
}
