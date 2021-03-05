<?php declare(strict_types = 1);

namespace App\Entity\Activity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 */
class TokenWithdrawnActivity extends UserAmountActivity
{
    public function getType(): int
    {
        return self::TOKEN_WITHDRAWN;
    }
}
