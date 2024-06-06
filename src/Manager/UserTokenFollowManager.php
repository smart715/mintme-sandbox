<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserTokenFollow;
use App\Exception\UserTokenFollowException;
use App\Mercure\PublisherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class UserTokenFollowManager implements UserTokenFollowManagerInterface
{
    /** @var EntityRepository<UserTokenFollow> $tokenFollowStatusRepository */
    private EntityRepository $tokenFollowStatusRepository;
    private EntityManagerInterface $entityManager;
    private PublisherInterface $publisher;

    public function __construct(
        EntityManagerInterface $entityManager,
        PublisherInterface $publisher
    ) {
        $this->tokenFollowStatusRepository = $entityManager->getRepository(UserTokenFollow::class);
        $this->entityManager = $entityManager;
        $this->publisher = $publisher;
    }

    public function manualFollow(Token $token, User $user): void
    {
        $this->asserUserIsNotOwner($token, $user);

        /** @var UserTokenFollow|null $userTokenFollow*/
        $userTokenFollow = $this->tokenFollowStatusRepository->findOneBy([
            'token' => $token,
            'user' => $user,
        ]);

        if (null === $userTokenFollow) {
            $userTokenFollow = new UserTokenFollow();
            $userTokenFollow
                ->setToken($token)
                ->setUser($user);
        }

        if (UserTokenFollow::FOLLOW_STATUS_FOLLOWED !== $userTokenFollow->getFollowStatus()) {
            $userTokenFollow->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_FOLLOWED);

            $this->entityManager->persist($userTokenFollow);
            $this->entityManager->flush();
        }

        $this->publishToMercure($userTokenFollow);
    }

    public function manualUnfollow(Token $token, User $user): void
    {
        $this->asserUserIsNotOwner($token, $user);

        /** @var UserTokenFollow|null $userTokenFollow*/
        $userTokenFollow = $this->tokenFollowStatusRepository->findOneBy([
            'token' => $token,
            'user' => $user,
        ]);

        if (null === $userTokenFollow) {
            $userTokenFollow = new UserTokenFollow();
            $userTokenFollow
                ->setToken($token)
                ->setUser($user);
        }

        if (UserTokenFollow::FOLLOW_STATUS_FOLLOWED === $userTokenFollow->getFollowStatus()) {
            $userTokenFollow->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_UNFOLLOWED);

            $this->entityManager->persist($userTokenFollow);
            $this->entityManager->flush();
        }

        $this->publishToMercure($userTokenFollow);
    }

    public function autoFollow(Token $token, User $user): void
    {
        $this->asserUserIsNotOwner($token, $user);

        /** @var UserTokenFollow|null $userTokenFollow*/
        $userTokenFollow = $this->tokenFollowStatusRepository->findOneBy([
            'token' => $token,
            'user' => $user,
        ]);

        if (null === $userTokenFollow) {
            $userTokenFollow = new UserTokenFollow();
            $userTokenFollow
                ->setToken($token)
                ->setUser($user);
        }

        if (UserTokenFollow::FOLLOW_STATUS_NEUTRAL !== $userTokenFollow->getFollowStatus()) {
            throw new UserTokenFollowException('', UserTokenFollowException::NOT_FIRST_FOLLOW);
        }

        $userTokenFollow->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_FOLLOWED);

        $this->entityManager->persist($userTokenFollow);
        $this->entityManager->flush();

        $this->publishToMercure($userTokenFollow);
    }

    public function getFollowStatus(Token $token, User $user): string
    {
        $userTokenFollow = $this->tokenFollowStatusRepository->findOneBy([
            'token' => $token,
            'user' => $user,
        ]);

        return null === $userTokenFollow
            ? UserTokenFollow::FOLLOW_STATUS_NEUTRAL
            : $userTokenFollow->getFollowStatus();
    }

    public function getFollowers(Token $token): array
    {
        /** @var UserTokenFollow[] $userTokenFollows */
        $userTokenFollows = $this->tokenFollowStatusRepository->findBy([
            'token' => $token,
            'followStatus' => UserTokenFollow::FOLLOW_STATUS_FOLLOWED,
        ]);

        $followers = [];

        foreach ($userTokenFollows as $userTokenFollow) {
            $followers[] = $userTokenFollow->getUser();
        }

        return $followers;
    }

    public function getFollowedTokens(User $user): array
    {
        /** @var UserTokenFollow[] $userTokenFollows */
        $userTokenFollows = $this->tokenFollowStatusRepository->findBy([
            'user' => $user,
            'followStatus' => UserTokenFollow::FOLLOW_STATUS_FOLLOWED,
        ]);

        $followedTokens = [];

        foreach ($userTokenFollows as $userTokenFollow) {
            $followedTokens[] = $userTokenFollow->getToken();
        }

        return $followedTokens;
    }

    public function isFollower(User $user, Token $token): bool
    {
        return UserTokenFollow::FOLLOW_STATUS_FOLLOWED === $this->getFollowStatus($token, $user);
    }

    /**
     * @throws UserTokenFollowException
     */
    private function asserUserIsNotOwner(Token $token, User $user): void
    {
        if ($user->getId() === $token->getOwnerId()) {
            throw new UserTokenFollowException('', UserTokenFollowException::USER_IS_OWNER);
        }
    }

    private function publishToMercure(UserTokenFollow $userTokenFollow): void
    {
        $this->publisher->publish('update-follow-status', $userTokenFollow);
    }
}
