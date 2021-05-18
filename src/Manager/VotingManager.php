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

    public function getById(int $id): ?Voting
    {
        return $this->repository->find($id);
    }

    public function getByOptionId(int $optionId): ?Voting
    {
        return $this->repository->getByOptionId($optionId);
    }

    public function getByIdForTradable(int $id, TradebleInterface $tradable): ?Voting
    {
        $filtered = array_filter($tradable->getVotings(), static fn(Voting $voting) => $voting->getId() === $id);

        return array_pop($filtered);
    }
}
