<?php

namespace App\Repository;

use App\Entity\Profile;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Model\UserInterface;

class ProfileRepository extends EntityRepository
{
    public function getProfileByUser(UserInterface $user): ?Profile
    {
        return $this->findOneBy(['user' => $user->getId()]);
    }
    public function getProfileByPageUrl(String $pageUrl): ?Profile
    {
        return $this->findOneBy(['page_url' => $pageUrl]);
    }
}
