<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\TokenSignupBonusCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TokenSignupBonusCode>
 * @codeCoverageIgnore
 */
class TokenSignupBonusCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenSignupBonusCode::class);
    }

    public function findByCode(string $code): ?TokenSignupBonusCode
    {
        return $this->findOneBy(['code' => $code]);
    }
}
