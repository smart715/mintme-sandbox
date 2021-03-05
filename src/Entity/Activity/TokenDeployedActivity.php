<?php declare(strict_types = 1);

namespace App\Entity\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 */
class TokenDeployedActivity extends Activity
{
    public function getType(): int
    {
        return self::TOKEN_DEPLOYED;
    }
}
