<?php declare(strict_types = 1);

namespace App\Repository\KnowledgeBase;

use App\Entity\KnowledgeBase\KnowledgeBase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KnowledgeBase>
 * @codeCoverageIgnore
 */
class KnowledgeBaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KnowledgeBase::class);
    }

    /**
     * @return KnowledgeBase[]
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('kb')
            ->leftJoin('kb.category', 'kbc')
            ->leftJoin('kb.subcategory', 'kbsc')
            ->addOrderBy('kbc.position', 'ASC')
            ->addOrderBy('kbsc.position', 'ASC')
            ->addOrderBy('kb.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findKbRelated(KnowledgeBase $kb, int $limit = 5): array
    {
        return $this->createQueryBuilder('kb')
            ->leftJoin('kb.category', 'kbc')
            ->leftJoin('kb.subcategory', 'kbsc')
            ->where('kb.category = :category')
            ->andWhere('kb.id != :kid')
            ->setParameter('category', $kb->getCategory())
            ->setParameter('kid', $kb->getId())
            ->addOrderBy('kbc.position', 'ASC')
            ->addOrderBy('kbsc.position', 'ASC')
            ->addOrderBy('kb.position', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
