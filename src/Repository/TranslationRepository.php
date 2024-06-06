<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Translation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Translation>
 * @method Translation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Translation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Translation[]    findAll()
 * @method Translation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @codeCoverageIgnore
 */
class TranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translation::class);
    }

    public function findTranslationBy(
        string $translationFor,
        string $keyLanguage,
        string $keyTranslation
    ): ?Translation {
        $query = $this->createQueryBuilder('t')
            ->andWhere('t.keyLanguage = :keyLanguage')
            ->andWhere('t.translationFor = :translationFor')
            ->andWhere('t.keyTranslation = :keyTranslation')
            ->setParameter('keyLanguage', $keyLanguage)
            ->setParameter('translationFor', $translationFor)
            ->setParameter('keyTranslation', $keyTranslation);

        return $query
            ->getQuery()
            ->getResult()[0] ?? null;
    }

    public function getAllTranslationByLanguageIndexedByPosition(
        string $translationFor,
        string $keyLanguage
    ): ?array {
        $query = $this->createQueryBuilder('t', 't.position')
            ->andWhere('t.keyLanguage = :keyLanguage')
            ->andWhere('t.translationFor = :translationFor')
            ->setParameter('keyLanguage', $keyLanguage)
            ->setParameter('translationFor', $translationFor)
            ->orderBy('t.position', 'ASC');

        return $query
            ->getQuery()
            ->getResult();
    }
}
