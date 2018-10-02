<?php

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\Token;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Model\UserInterface;

class ProfileRepository extends EntityRepository
{
    public function getProfileByUser(UserInterface $user): ?Profile
    {
        return $this->findOneBy(['user' => $user->getId()]);
    }

    public function getProfileByPageUrl(string $pageUrl): ?Profile
    {
        return $this->findOneBy(['page_url' => $pageUrl]);
    }

    public function findByToken(Token $token): Profile
    {
        return $this->findOneBy(['token' => $token->getId()]);
    }
}
