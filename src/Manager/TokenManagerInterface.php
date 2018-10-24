<?php

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;

interface TokenManagerInterface
{
    public function findByName(string $name): ?Token;

    public function getOwnToken(): ?Token;
}
