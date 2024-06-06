<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\TokenDeploy;

interface TokenDeployManagerInterface
{
    public function findByAddress(string $address): ?TokenDeploy;

    public function getTotalCostPerCrypto(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array;

    public function findByAddressAndCrypto(string $address, Crypto $crypto): ?TokenDeploy;
}
