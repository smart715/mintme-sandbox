<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Blacklist\Blacklist;
use libphonenumber\PhoneNumber;

interface BlacklistManagerInterface
{
    public function isBlacklistedAirdropDomain(string $url, bool $sensitive = false): bool;
    public function isBlacklistedEmail(string $email, bool $sensitive = false): bool;
    public function isBlacklistedToken(string $token, bool $sensitive = false): bool;
    public function isBlackListedNumber(PhoneNumber $phoneNumber): bool;
    public function isBlacklistedCodeCountry(PhoneNumber $phoneNumber, string $providerName): bool;
    public function add(string $value, string $type, bool $flush = true): void;
    public function bulkDelete(string $type, bool $flush = true): void;
    public function bulkAdd(array $values, string $type, int $batchSize = 20): void;

    /**
     * @param string|null $type
     * @return array<Blacklist>
     */
    public function getList(?string $type = null): array;

    /**
     * returns values only of the given type as flat array (e.g. ['foo', 'bar'])
     *
     * @param string $type
     * @return array<string>
     */
    public function getValues(string $type): array;
}
