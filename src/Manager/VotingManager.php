<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Entity\Voting\Voting;
use App\Repository\CryptoVotingRepository;
use App\Repository\TokenVotingRepository;
use App\Repository\VotingRepository;

class VotingManager implements VotingManagerInterface
{
    private VotingRepository $repository;
    private TokenVotingRepository $tokenVotingRepository;
    private CryptoVotingRepository $cryptoVotingRepository;

    public function __construct(
        VotingRepository $repository,
        TokenVotingRepository $tokenVotingRepository,
        CryptoVotingRepository $cryptoVotingRepository
    ) {
        $this->repository = $repository;
        $this->tokenVotingRepository = $tokenVotingRepository;
        $this->cryptoVotingRepository = $cryptoVotingRepository;
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

    public function getBySlugForTradable(string $slug, TradableInterface $tradable): ?Voting
    {
        $filtered = array_filter($tradable->getVotings(), static fn(Voting $voting) => $voting->getSlug() === $slug);

        return array_pop($filtered);
    }

    public function getAllCreatedByUserAndTokenOwner(User $creator, User $tokenOwner): array
    {
        return $this->tokenVotingRepository->getVotingsByCreatorIdAndProfileId(
            $creator->getId(),
            $tokenOwner->getProfile()->getId(),
        );
    }

    public function countOpenVotingsByToken(Token $token): int
    {
        return $this->tokenVotingRepository->countOpenVotingsByToken($token);
    }

    public function countVotingsByToken(Token $token): int
    {
        return $this->tokenVotingRepository->countVotingsByToken($token);
    }

    public function countOpenVotings(): int
    {
        return $this->cryptoVotingRepository->countOpenVotings();
    }

    public function getAllByTokenUser(Token $token, User $user): array
    {
        return $this->tokenVotingRepository->findBy(['token' => $token, 'creator' => $user]);
    }
}
