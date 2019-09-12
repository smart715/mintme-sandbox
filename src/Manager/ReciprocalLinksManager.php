<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\ReciprocalLinks;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ReciprocalLinksManager implements ReciprocalLinksManagerInterface
{
    /** @var EntityRepository */
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(ReciprocalLinks::class);
    }

    /**
     * @return ReciprocalLinks[]
     */
    public function getAll(): array
    {
        return $this->repository->findAll();
    }
}
