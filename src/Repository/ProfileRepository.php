<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FOS\UserBundle\Model\UserInterface;

/**
 * @extends ServiceEntityRepository<Profile>
 * @codeCoverageIgnore
 */
class ProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profile::class);
    }

    public function getProfileByUser(UserInterface $user): ?Profile
    {
        return $this->findOneBy(['user' => $user->getId()]);
    }

    public function getProfileById(int $id): ?Profile
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function getProfileByNickname(string $nickname): ?Profile
    {
        return $this->findOneBy(['nickname' => $nickname]);
    }

    /** @return Profile[] */
    public function findAllProfileWithEmptyDescriptionAndNotAnonymous(int $numberOfReminder = 14): array
    {
        $query = $this->createQueryBuilder('p')
            ->innerJoin('p.user', 'u', 'p.user = u.id')
            ->where('p.description is null or p.description = :emptyString')
            ->andWhere('p.anonymous = 0')
            ->andWhere('p.numberOfReminder <> :numberOfReminder')
            ->andWhere('p.nextReminderDate = :nextReminderDate OR p.nextReminderDate is null')
            ->setParameter('emptyString', '')
            ->setParameter('numberOfReminder', $numberOfReminder)
            ->setParameter('nextReminderDate', \Date('Y-m-d'));

        return $query->getQuery()->getResult();
    }
}
