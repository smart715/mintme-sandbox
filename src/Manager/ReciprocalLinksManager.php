<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\ReciprocalLinks;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ReciprocalLinksManager implements ReciprocalLinksManagerInterface
{
    /** @var EntityRepository<ReciprocalLinks> $repository */
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $repository = $entityManager->getRepository(ReciprocalLinks::class);
        $this->repository = $repository;
    }

    /**
     * @return ReciprocalLinks[]
     */
    public function getAll(): array
    {
        return $this->repository->findAll();
    }
}
