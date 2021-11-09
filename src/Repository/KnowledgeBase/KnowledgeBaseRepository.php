<?php declare(strict_types = 1);

namespace App\Repository\KnowledgeBase;

use App\Entity\KnowledgeBase\KnowledgeBase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KnowledgeBaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KnowledgeBase::class);
    }

    /**
     * @codeCoverageIgnore
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
}
