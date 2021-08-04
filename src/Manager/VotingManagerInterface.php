<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\TradebleInterface;
use App\Entity\Voting\Voting;
use App\Repository\VotingRepository;

interface VotingManagerInterface
{
    public function getRepository(): VotingRepository;
    public function getById(int $id): ?Voting;
    public function getBySlug(string $slug): ?Voting;
    public function getByOptionId(int $optionId): ?Voting;
    public function getBySlugForTradable(string $slug, TradebleInterface $tradable): ?Voting;
}
