<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\BonusBalanceTransaction;
use App\Entity\TradableInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class BonusBalanceTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonusBalanceTransaction::class);
    }

    public function getBalancesByUser(User $user): array
    {
        return $this->createQueryBuilder('bbt')
            ->select('(SELECT SUM(d.amount) FROM App:BonusBalanceTransaction d '.
                'WHERE d.type = \'deposit\' AND d.user = bbt.user AND (d.token = bbt.token OR d.crypto = bbt.crypto)) as deposit')
            ->addSelect('(SELECT SUM(w.amount) FROM App:BonusBalanceTransaction w '.
                'WHERE w.type = \'withdraw\' AND w.user = bbt.user AND (w.token = bbt.token OR w.crypto = bbt.crypto)) as withdraw')
            ->addSelect('IDENTITY(bbt.token) as token_id ')
            ->addSelect('IDENTITY(bbt.crypto) as crypto_id ')
            ->where('bbt.user = :user')
            ->groupBy('bbt.user')
            ->addGroupBy('bbt.token')
            ->addGroupBy('bbt.crypto')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function getBalance(User $user, TradableInterface $tradable): ?array
    {
        $column = $tradable->getTradableType();

        return $this->createQueryBuilder('bbt')
            ->select('(SELECT SUM(d.amount) FROM App:BonusBalanceTransaction d '.
                'WHERE d.type = \'deposit\' AND d.user = :user AND d.'.$column.' = :tradable) as deposit')
            ->addSelect('(SELECT SUM(w.amount) FROM App:BonusBalanceTransaction w '.
                'WHERE w.type = \'withdraw\' AND w.user = :user AND w.'.$column.' = :tradable) as withdraw')
            ->addSelect('IDENTITY(bbt.'.$column.') as '.$column)
            ->where('bbt.user = :user')
            ->andWhere('bbt.'.$column.' = :tradable')
            ->groupBy('bbt.user')
            ->addGroupBy('bbt.'.$column)
            ->setParameter('user', $user)
            ->setParameter('tradable', $tradable)
            ->getQuery()
            ->getResult()[0] ?? null;
    }

    public function getTransactions(User $user, int $offset, int $limit): array
    {
        return $this->findBy(['user' => $user], ['id' => 'DESC'], $limit, $offset);
    }
}
