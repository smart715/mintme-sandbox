<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Bonus;
use Doctrine\ORM\EntityRepository;

class BonusRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function getPaidCount(): int
    {
        return $this->count(['status' => Bonus::PAID_STATUS, 'quantityWeb' => Bonus::BONUS_WEB]);
    }
}
