<?php declare(strict_types = 1);

namespace App\Communications;

interface ExternalServiceIdsMapperInterface
{
    public function getCryptoId(string $symbol): ?string;
    public function getSymbolFromId(string $id): ?string;
}
