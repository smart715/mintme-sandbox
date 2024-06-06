<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\TokenDeploy;
use App\Repository\TokenDeployRepository;

class TokenDeployManager implements TokenDeployManagerInterface
{
    public TokenDeployRepository $repository;

    public function __construct(TokenDeployRepository $repository)
    {
        $this->repository = $repository;
    }

    public function findByAddress(string $address): ?TokenDeploy
    {
        return $this->repository->findByAddress($address);
    }

    public function getTotalCostPerCrypto(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->repository->getTotalCostPerCrypto($startDate, $endDate);
    }

    public function findByAddressAndCrypto(string $address, Crypto $crypto): ?TokenDeploy
    {
        return $this->repository->findByAddressAndCrypto($address, $crypto);
    }
}
