<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Voting\Option;
use App\Entity\Voting\Voting;

interface VotingOptionManagerInterface
{
    public function getById(int $id): ?Option;
    public function getByIdFromVoting(int $id, Voting $voting): ?Option;
}
