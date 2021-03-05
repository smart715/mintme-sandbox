<?php declare(strict_types = 1);

namespace App\Entity\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 */
class TokenDepositedActivity extends UserAmountActivity
{
    public function getType(): int
    {
        return self::TOKEN_DEPOSITED;
    }
}
