<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\DiscordRole;
use App\Entity\Token\Token;
use App\Wallet\Money\MoneyWrapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Money\Money;

/** @codeCoverageIgnore */
class DiscordRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscordRole::class);
    }

    public function findByTokenAndAmount(Token $token, Money $amount): ?DiscordRole
    {
        $result = $this->createQueryBuilder('dr')
            ->where('dr.token = :token')
            ->andWhere(
                'to_number(dr.requiredBalance, :subunit, :showSubunit) <= to_number(:amount, :subunit, :showSubunit)'
            )
            ->orderBy('to_number(dr.requiredBalance, :subunit, :showSubunit)', 'DESC')
            ->setMaxResults(1)
            ->setParameter('token', $token)
            ->setParameter('amount', $amount->getAmount())
            ->setParameter('subunit', MoneyWrapper::TOK_SUBUNIT)
            ->setParameter('showSubunit', Token::TOKEN_SUBUNIT)
            ->getQuery()
            ->getResult();

        return $result[0] ?? null;
    }
}
