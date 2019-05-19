<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Blacklist;
use App\Repository\BlacklistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

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
        return $this->repository->matchValue($value, $type, $sensetive);
    }

    public function addToBlacklist(string $value, string $type): void
    {
        $this->add($value, $type);
        $this->em->flush();
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

    public function migrate(array $names, string $type): void
    {
        $list = $this->getList($type);

        foreach (array_unique($names) as $name) {
            if (!$this->isValueExists($name, $list)) {
                $this->add($name, $type);
                $this->em->flush();
            }
        }
    }

    private function add(string $value, string $type): void
    {
        $this->em->persist(new Blacklist($value, $type));
    }

    /** @param array<Blacklist> $list */
    private function isValueExists(string $value, array $list): bool
    {
        foreach ($list as $item) {
            if ($item->getValue() === $value) {
                return true;
            }
        }

        return false;
    }
}
