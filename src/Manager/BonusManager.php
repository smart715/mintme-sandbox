<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Bonus;
use App\Repository\BonusRepository;
use Doctrine\ORM\EntityManagerInterface;

class BonusManager implements BonusManagerInterface
{
    /** @var BonusRepository */
    private $bonusRepo;

    public function __construct(EntityManagerInterface $entityManager)
    {
        /** @var BonusRepository $repo */
        $repo = $entityManager->getRepository(Bonus::class);
        $this->bonusRepo = $repo;
    }

    public function isLimitReached(int $limit, string $type): bool
    {
        return $limit <= $this->bonusRepo->getPaidSum($type);
    }
}
