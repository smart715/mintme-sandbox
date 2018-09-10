<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;

class ProfileManager implements ProfileManagerInterface
{
    /** @var ProfileRepository */
    private $profileRepository;

    /** @var UserRepository */
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->profileRepository = $entityManager->getRepository(Profile::class);
        $this->userRepository = $entityManager->getRepository(User::class);
    }

    public function getProfile(UserInterface $user): ?Profile
    {
        return $this->profileRepository->getProfileByUser($user);
    }

    public function findByEmail(string $email): ?Profile
    {
        $user = $this->userRepository->findByEmail($email);
        return is_null($user) ? null : $this->getProfile($user);
    }

    public function lockChangePeriod(Profile $profile): void
    {
        $profile->setNameChangedDate(new DateTime('+1 month'));
    }
}
