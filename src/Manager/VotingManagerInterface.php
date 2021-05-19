<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\TradebleInterface;
use App\Entity\Voting\Voting;

interface VotingManagerInterface
{
    public function getById(int $id): ?Voting;
    public function getByOptionId(int $optionId): ?Voting;
    public function getByIdForTradable(int $id, TradebleInterface $tradable): ?Voting;
}
