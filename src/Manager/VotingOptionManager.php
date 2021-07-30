<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Voting\Option;
use App\Entity\Voting\Voting;
use App\Repository\VotingOptionRepository;

class VotingOptionManager implements VotingOptionManagerInterface
{
    private VotingOptionRepository $repository;

    public function __construct(VotingOptionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getById(int $id): ?Option
    {
        return $this->repository->find($id);
    }

    public function getByIdFromVoting(int $id, Voting $voting): ?Option
    {
        $filtered = array_filter($voting->getOptions(), static fn(Option $option) => $option->getId() === $id);

        return array_pop($filtered);
    }
}
