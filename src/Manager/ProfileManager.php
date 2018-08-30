<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use DateTime;
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
    public function getProfileByPageUrl(String $pageUrl): ?Profile
    {
        return $this->profileRepository->getProfileByPageUrl($pageUrl);
    }

    public function lockChangePeriod(Profile $profile): void
    {
        $profile->setNameChangedDate(new DateTime('+1 month'));
    }
    
    public function generatePageUrl(Profile $profile): String
    {
        if (empty($profile->getLastName()))
            return "";

        $currentPageUrl = $profile->getLastName();
        if (!empty($profile->getFirstName()))
            $currentPageUrl .= "." . $profile->getFirstName();
        
        $checkExistProfile = $this->profileRepository->getProfileByPageUrl($currentPageUrl);
        return
            (null === $checkExistProfile
                || ($currentPageUrl === $checkExistProfile->getPageUrl()
                    && $profile->getUser() === $checkExistProfile->getUser()))
            ? strtolower($currentPageUrl) : strtolower($currentPageUrl) . "." . $this->randomString(6);
    }
    private function randomString(int $length): String
    {
        $keys = array_merge(range(0, 9), range('a', 'z'));
        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }

        return $key;
    }
}
