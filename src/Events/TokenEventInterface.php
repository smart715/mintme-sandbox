<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Token\Token;

interface TokenEventInterface
{
    public function getToken(): Token;
}
