<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Blacklist;

interface BlacklistManagerInterface
{
    public function isBlacklistedAirdropDomain(string $url, bool $sensitive = false): bool;
    public function isBlacklistedEmail(string $email, bool $sensitive = false): bool;
    public function isBlacklistedToken(string $token, bool $sensitive = false): bool;
    public function add(string $value, string $type, bool $flush = true): void;
    public function bulkDelete(string $type, bool $flush = true): void;
    public function bulkAdd(array $values, string $type, int $batchSize = 20): void;

    /**
     * @param string|null $type
     * @return array<Blacklist>
     */
    public function getList(?string $type = null): array;
}
