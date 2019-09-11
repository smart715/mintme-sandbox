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
        $this->bonusRepo = $entityManager->getRepository(Bonus::class);
    }

    public function isLimitReached(int $limit): bool
    {
        return $limit <= Bonus::BONUS_WEB * $this->bonusRepo->getPaidCount();
    }
}
