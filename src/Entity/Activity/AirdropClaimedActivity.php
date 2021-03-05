<?php declare(strict_types = 1);

namespace App\Entity\Activity;

use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 */
class AirdropClaimedActivity extends UserAmountActivity
{
    protected string $amount_symbol = Symbols::TOK; // phpcs:ignore

    public function getType(): int
    {
        return self::AIRDROP_CLAIMED;
    }
}
