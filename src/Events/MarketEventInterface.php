<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Crypto;
use App\Events\Activity\ActivityEventInterface;

interface MarketEventInterface extends ActivityEventInterface, TokenEventInterface
{
    public function getCrypto(): Crypto;
}
