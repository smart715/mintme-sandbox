<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\Token\Token;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Model\UserInterface;

class ProfileRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function getProfileByUser(UserInterface $user): ?Profile
    {
        return $this->findOneBy(['user' => $user->getId()]);
    }

    /** @codeCoverageIgnore */
    public function getProfileByNickname(string $nickname): ?Profile
    {
        return $this->findOneBy(['nickname' => $nickname]);
    }
}
