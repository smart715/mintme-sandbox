<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\User;
use App\OrmAdapter\OrmAdapterInterface;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;

class ProfileManager implements ProfileManagerInterface
{
    /** @var ProfileRepository */
    private $profileRepository;
    
    /** @var EntityManagerInterface */
    private $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->profileRepository = $entityManager->getRepository(Profile::class);
    }
    public function createProfile(UserInterface $user): ?Profile
    {
        return $this->doCreateProfile($user, function (Profile $profile): void {
        });
    }
    
    public function createProfileReferral(UserInterface $user, string $referralCode): ?Profile
    {
        return $this->doCreateProfile(
            $user,
            function (Profile $profile) use ($referralCode): void {
                $referrer = $this->profileRepository->findReferrer($referralCode);

                if (is_null($referrer))
                    return;

                $profile->referenceBy($referrer);
            }
        );
    }
    
    public function getProfile(UserInterface $user): ?Profile
    {
        return $this->profileRepository->getProfileByUser($user);
    }
    
    public function lockChangePeriod(Profile $profile): void
    {
        $profile->setNameChangedDate(new DateTime('+1 month'));
    }
    
    private function doCreateProfile(UserInterface $user, callable $changeProfile): ?Profile
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($user->getId());
        if (null === $user)
            return  null;
        
        $profile = new Profile($user);
        $changeProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();
        return $profile;
    }
}
