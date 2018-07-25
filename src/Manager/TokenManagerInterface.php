<?php

namespace App\Manager;

use App\Entity\Token;

interface TokenManagerInterface
{
    public function createToken(): Token;

    public function findByName(string $name): ?Token;

    public function getOwnToken(): ?Token;
}
