<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;
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

    /** @codeCoverageIgnore */
    public function findAllProfileWithEmptyDescriptionAndNotAnonymous(int $numberOfReminder = 14): ?array
    {
        $query = $this->createQueryBuilder('p')
            ->innerJoin('p.user', 'u', 'p.user = u.id')
            ->where('p.description is null')
            ->andWhere('p.anonymous = 0')
            ->andWhere('p.numberOfReminder <> :numberOfReminder')
            ->andWhere('p.nextReminderDate = :nextReminderDate OR p.nextReminderDate is null')
            ->setParameter('numberOfReminder', $numberOfReminder)
            ->setParameter('nextReminderDate', \Date('Y-m-d'));

        return $query->getQuery()->getResult();
    }
}
