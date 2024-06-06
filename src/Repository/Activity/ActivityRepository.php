<?php declare(strict_types = 1);

namespace App\Repository\Activity;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @codeCoverageIgnore
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * @return Activity[]
     */
    public function getUniqueLast(int $offset, int $limit): array
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->groupBy('a.type')
            ->addGroupBy('a.context')
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function getLastByTypesAndUniqueToken(array $types, int $limit): array
    {
        $sql = "
            SELECT JSON_UNQUOTE(JSON_EXTRACT(a.context, '$.fullTokenName')) AS fullTokenName,
                JSON_UNQUOTE(JSON_EXTRACT(a.context, '$.symbol')) AS symbol
            FROM activity a
            WHERE a.type IN (:types)
            GROUP BY fullTokenName
            ORDER BY MAX(a.created_at) DESC
            LIMIT :limit
        ";

        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->_em);
        $rsm->addScalarResult('fullTokenName', 'fullTokenName');
        $rsm->addScalarResult('symbol', 'symbol');

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameter('types', $types);
        $query->setParameter('limit', $limit);

        return $query->getResult();
    }
}
