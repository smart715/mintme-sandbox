<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Blacklist;
use App\Repository\BlacklistRepository;
use Doctrine\ORM\EntityManagerInterface;

class BlacklistManager implements BlacklistManagerInterface
{
    /** @var BlacklistRepository */
    private $repository;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $this->em->getRepository(Blacklist::class);
    }

    public function isBlacklisted(string $value, string $type, bool $sensetive = true): bool
    {
        if ('email' === $type) {
            $value = substr($value, strrpos($value, '@') + 1);

            return $this->repository->matchValue($value, $type, $sensetive);
        }

        return $this->repository->matchValue($value, $type, $sensetive);
    }

    public function addToBlacklist(string $value, string $type, bool $flush = true): void
    {
        $this->add($value, $type);

        if ($flush) {
            $this->em->flush();
        }
    }

    /** {@inheritDoc} */
    public function getList(?string $type = null): array
    {
        if (!$type) {
            return $this->repository->findAll();
        }

        return $this->repository->findBy([
            'type' => $type,
        ]);
    }

    private function add(string $value, string $type): void
    {
        $this->em->persist(new Blacklist($value, $type));
    }
}
