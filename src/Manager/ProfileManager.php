<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\User;
use App\Entity\Token;
use App\Repository\ProfileRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class ProfileManager implements ProfileManagerInterface
{
    /** @var ProfileRepository */
    private $profileRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->profileRepository = $entityManager->getRepository(Profile::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfile($user): ?Profile
    {
        if (!$user instanceof User) {
            return null;
        }

        return $this->profileRepository->getProfileByUser($user);
    }

    public function findByToken(Token $token): Profile
    {
        return $this->profileRepository->findByToken($token);
    }

    public function lockChangePeriod(Profile $profile): void
    {
        $profile->setNameChangedDate(new DateTime('+1 month'));
    }
}
