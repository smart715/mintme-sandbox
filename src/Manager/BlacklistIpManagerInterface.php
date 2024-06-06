<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Blacklist\BlacklistIp;
use App\Repository\BlacklistIpRepository;
use Doctrine\ORM\QueryBuilder;

interface BlacklistIpManagerInterface
{
    public function getBlacklistIpByAddress(string $address): ?BlacklistIp;
    public function isBlacklistedIp(?BlacklistIp $blacklistIp): bool;
    public function getRepository(): BlacklistIpRepository;
    public function decrementChances(?BlacklistIp $blacklistIp, string $address): int;
    public function getBlacklistIpByNumberOfDaysQueryBuilder(int $days): QueryBuilder;
    public function getWaitedHours(BlacklistIp $blacklist): int;
    public function getMustWaitHours(BlacklistIp $blacklist): int;
    public function deleteBlacklistIp(?BlacklistIp $blacklistIp): void;
}
