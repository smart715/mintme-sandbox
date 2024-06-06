<?php declare(strict_types = 1);

namespace App\Manager;

use App\Config\BlacklistIpConfig;
use App\Entity\Blacklist\BlacklistIp;
use App\Repository\BlacklistIpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class BlacklistIpManager implements BlacklistIpManagerInterface
{
    private BlacklistIpRepository $repository;
    private EntityManagerInterface $em;
    private BlacklistIpConfig $blacklistIpConfig;

    public function __construct(
        EntityManagerInterface $em,
        BlacklistIpConfig $blacklistIpConfig,
        BlacklistIpRepository $repository
    ) {
        $this->em = $em;
        $this->repository = $repository;
        $this->blacklistIpConfig = $blacklistIpConfig;
    }

    public function getBlacklistIpByNumberOfDaysQueryBuilder(int $days): QueryBuilder
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('blacklist_ip');
        $dateTimeImmutable = new \DateTimeImmutable('-'. $days .' day');
        $from = $dateTimeImmutable->setTime(0, 0)->format('Y-m-d H:i:s');
        $to = $dateTimeImmutable->setTime(23, 59, 59)->format('Y-m-d H:i:s');
        $queryBuilder->where('blacklist_ip.createdAt between :from AND :to')
            ->setParameters([
                'from' => $from,
                'to' => $to,
            ]);

        return $queryBuilder;
    }

    public function getWaitedHours(BlacklistIp $blacklist): int
    {
        $now = (new \DateTimeImmutable())->getTimestamp();
        $waitedTime =  $now - $blacklist->getUpdatedAt()->getTimestamp();

        return (int) ($waitedTime / 3600);
    }

    public function isBlacklistedIp(?BlacklistIp $blacklistIp): bool
    {
        if ($blacklistIp && 0 === $blacklistIp->getChances()) {
            if ($this->getWaitedHours($blacklistIp) < $this->blacklistIpConfig->getMaxHours()) {
                return true;
            }

            $this->deleteBlacklistIp($blacklistIp);
        }

        return false;
    }

    public function getMustWaitHours(BlacklistIp $blacklist): int
    {
        $mustWait = (int) ($this->blacklistIpConfig->getMaxHours() - $this->getWaitedHours($blacklist));

        return 0 < $mustWait
            ? $mustWait
            : 1;
    }

    public function decrementChances(?BlacklistIp $blacklistIp, string $address): int
    {
        $chances = !$blacklistIp
            ? $this->blacklistIpConfig->getMaxChances() - 1
            : $blacklistIp->getChances() - 1;

        if (!$blacklistIp) {
            $blacklistIp = new BlacklistIp();
            $blacklistIp->setAddress($address);
        }

        $blacklistIp->setChances($chances);
        $this->em->persist($blacklistIp);
        $this->em->flush();

        return $chances;
    }

    public function getRepository(): BlacklistIpRepository
    {
        return $this->repository;
    }

    public function getBlacklistIpByAddress(string $address): ?BlacklistIp
    {
        return $this->repository->findByIp($address);
    }

    public function deleteBlacklistIp(?BlacklistIp $blacklistIp): void
    {
        if (!$blacklistIp) {
            return;
        }

        $this->em->remove($blacklistIp);
        $this->em->flush();
    }
}
