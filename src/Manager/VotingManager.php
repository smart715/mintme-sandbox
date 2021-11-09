<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\TradebleInterface;
use App\Entity\Voting\Voting;
use App\Repository\VotingRepository;

class VotingManager implements VotingManagerInterface
{
    private VotingRepository $repository;

    public function __construct(VotingRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function getRepository(): VotingRepository
    {
        return $this->repository;
    }

    public function getById(int $id): ?Voting
    {
        return $this->repository->find($id);
    }

    public function getBySlug(string $slug): ?Voting
    {
        return $this->repository->findOneBy(['slug' => $slug]);
    }

    public function getByOptionId(int $optionId): ?Voting
    {
        return $this->repository->getByOptionId($optionId);
    }

    public function getBySlugForTradable(string $slug, TradebleInterface $tradable): ?Voting
    {
        $filtered = array_filter($tradable->getVotings(), static fn(Voting $voting) => $voting->getSlug() === $slug);

        return array_pop($filtered);
    }
}
