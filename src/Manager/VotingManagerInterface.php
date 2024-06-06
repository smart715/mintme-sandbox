<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Entity\Voting\TokenVoting;
use App\Entity\Voting\Voting;
use App\Repository\VotingRepository;

interface VotingManagerInterface
{
    public function getRepository(): VotingRepository;
    public function getById(int $id): ?Voting;
    public function getBySlug(string $slug): ?Voting;
    public function getByOptionId(int $optionId): ?Voting;
    public function getBySlugForTradable(string $slug, TradableInterface $tradable): ?Voting;

    /**
     * @param Token $token
     * @param User $user
     * @return TokenVoting[]
     */
    public function getAllByTokenUser(Token $token, User $user): array;
    public function countOpenVotingsByToken(Token $token): int;
    public function countVotingsByToken(Token $token): int;
    public function countOpenVotings(): int;
    public function getAllCreatedByUserAndTokenOwner(User $creator, User $tokenOwner): array;
}
