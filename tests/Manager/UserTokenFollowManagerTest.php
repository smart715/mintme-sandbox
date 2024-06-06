<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserTokenFollow;
use App\Exception\UserTokenFollowException;
use App\Manager\UserTokenFollowManager;
use App\Mercure\PublisherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserTokenFollowManagerTest extends TestCase
{
    /**
     * @dataProvider manualFollowDataProvider
     */
    public function testManualFollow(
        bool $isOwner,
        ?string $oldFollowStatus
    ): void {
        $token = $this->createMock(Token::class);
        $token->expects($this->once())
            ->method('getOwnerId')
            ->willReturn(1);

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn($isOwner ? 1 : 2);

        $em = $this->createMock(EntityManagerInterface::class);
        $utfr = $this->createMock(EntityRepository::class);
        $p = $this->createMock(PublisherInterface::class);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(UserTokenFollow::class)
            ->willReturn($utfr);

        $tokenFollowStatusManager = new UserTokenFollowManager(
            $em,
            $p
        );

        if ($isOwner) {
            try {
                $tokenFollowStatusManager->manualFollow($token, $user);
            } catch (UserTokenFollowException $exception) {
                $this->assertEquals(UserTokenFollowException::USER_IS_OWNER, $exception->getCode());

                return;
            }

            $this->fail();
        }

        $oldUserTokenFollow = null;

        if (null !== $oldFollowStatus) {
            $oldUserTokenFollow = new UserTokenFollow();
            $oldUserTokenFollow
                ->setUser($user)
                ->setToken($token)
                ->setFollowStatus($oldFollowStatus);
        }

        $utfr->expects($this->once())
            ->method('findOneBy')
            ->with([
                'token' => $token,
                'user' => $user,
            ])
            ->willReturn($oldUserTokenFollow);

        $expectedUserTokenFollow = new UserTokenFollow();
        $expectedUserTokenFollow
            ->setUser($user)
            ->setToken($token)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_FOLLOWED);

        if (UserTokenFollow::FOLLOW_STATUS_FOLLOWED !== $oldFollowStatus) {
            $em->expects($this->once())
                ->method('persist')
                ->with($expectedUserTokenFollow);

            $em->expects($this->once())
                ->method('flush');
        }

        $p->expects($this->once())
            ->method('publish')
            ->with('update-follow-status', $expectedUserTokenFollow);

        $tokenFollowStatusManager->manualFollow($token, $user);
    }

    /**
     * @dataProvider manualFollowDataProvider
     */
    public function testManualUnfollow(
        bool $isOwner,
        ?string $oldFollowStatus
    ): void {
        $token = $this->createMock(Token::class);
        $token->expects($this->once())
            ->method('getOwnerId')
            ->willReturn(1);

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn($isOwner ? 1 : 2);

        $em = $this->createMock(EntityManagerInterface::class);
        $utfr = $this->createMock(EntityRepository::class);
        $p = $this->createMock(PublisherInterface::class);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(UserTokenFollow::class)
            ->willReturn($utfr);

        $tokenFollowStatusManager = new UserTokenFollowManager(
            $em,
            $p
        );

        if ($isOwner) {
            try {
                $tokenFollowStatusManager->manualFollow($token, $user);
            } catch (UserTokenFollowException $exception) {
                $this->assertEquals(UserTokenFollowException::USER_IS_OWNER, $exception->getCode());

                return;
            }

            $this->fail();
        }

        $oldUserTokenFollow = null;

        if (null !== $oldFollowStatus) {
            $oldUserTokenFollow = new UserTokenFollow();
            $oldUserTokenFollow
                ->setUser($user)
                ->setToken($token)
                ->setFollowStatus($oldFollowStatus);
        }

        $utfr->expects($this->once())
            ->method('findOneBy')
            ->with([
                'token' => $token,
                'user' => $user,
            ])
            ->willReturn($oldUserTokenFollow);

        $expectedUserTokenFollow = new UserTokenFollow();
        $expectedUserTokenFollow
            ->setUser($user)
            ->setToken($token)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_UNFOLLOWED);

        if (null === $oldFollowStatus || UserTokenFollow::FOLLOW_STATUS_NEUTRAL === $oldFollowStatus) {
            $expectedUserTokenFollow->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_NEUTRAL);
        }

        if (UserTokenFollow::FOLLOW_STATUS_FOLLOWED === $oldFollowStatus) {
            $em->expects($this->once())
                ->method('persist')
                ->with($expectedUserTokenFollow);

            $em->expects($this->once())
                ->method('flush');
        }

        $p->expects($this->once())
            ->method('publish')
            ->with('update-follow-status', $expectedUserTokenFollow);

        $tokenFollowStatusManager->manualUnfollow($token, $user);
    }

    /**
     * @dataProvider manualFollowDataProvider
     */
    public function testAutoFollow(
        bool $isOwner,
        ?string $oldFollowStatus
    ): void {
        $token = $this->createMock(Token::class);
        $token->expects($this->once())
            ->method('getOwnerId')
            ->willReturn(1);

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn($isOwner ? 1 : 2);

        $em = $this->createMock(EntityManagerInterface::class);
        $utfr = $this->createMock(EntityRepository::class);
        $p = $this->createMock(PublisherInterface::class);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(UserTokenFollow::class)
            ->willReturn($utfr);

        if ($isOwner) {
            $tokenFollowStatusManager = new UserTokenFollowManager(
                $em,
                $p
            );

            try {
                $tokenFollowStatusManager->manualFollow($token, $user);
            } catch (UserTokenFollowException $exception) {
                $this->assertEquals(UserTokenFollowException::USER_IS_OWNER, $exception->getCode());

                return;
            }

            $this->fail();
        }

        $oldUserTokenFollow = null;

        if (null !== $oldFollowStatus) {
            $oldUserTokenFollow = new UserTokenFollow();
            $oldUserTokenFollow
                ->setUser($user)
                ->setToken($token)
                ->setFollowStatus($oldFollowStatus);
        }

        $utfr->expects($this->once())
            ->method('findOneBy')
            ->with([
                'token' => $token,
                'user' => $user,
            ])
            ->willReturn($oldUserTokenFollow);

        if (null === $oldFollowStatus || UserTokenFollow::FOLLOW_STATUS_NEUTRAL === $oldFollowStatus) {
            $expectedUserTokenFollow = new UserTokenFollow();
            $expectedUserTokenFollow
                ->setUser($user)
                ->setToken($token)
                ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_FOLLOWED);

            $em->expects($this->once())
                ->method('persist')
                ->with($expectedUserTokenFollow);

            $em->expects($this->once())
                ->method('flush');

            $p->expects($this->once())
                ->method('publish')
                ->with('update-follow-status', $expectedUserTokenFollow);
        }

        $tokenFollowStatusManager = new UserTokenFollowManager(
            $em,
            $p
        );

        try {
            $tokenFollowStatusManager->autoFollow($token, $user);
        } catch (UserTokenFollowException $exception) {
            $this->assertContains($oldFollowStatus, [
                UserTokenFollow::FOLLOW_STATUS_FOLLOWED,
                UserTokenFollow::FOLLOW_STATUS_UNFOLLOWED,
            ]);
            $this->assertEquals(UserTokenFollowException::NOT_FIRST_FOLLOW, $exception->getCode());
        }
    }

    /**
     * @dataProvider getTokenFollowStatusDataProvider
     */
    public function testGetTokenFollowStatus(
        Token $token,
        User $user,
        ?UserTokenFollow $userTokenFollow,
        string $expectedResponse
    ): void {
        $em = $this->createMock(EntityManagerInterface::class);
        $utfr = $this->createMock(EntityRepository::class);
        $p = $this->createMock(PublisherInterface::class);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(UserTokenFollow::class)
            ->willReturn($utfr);

        $utfr->expects($this->once())
            ->method('findOneBy')
            ->with([
                'token' => $token,
                'user' => $user,
            ])
            ->willReturn($userTokenFollow);

        $tokenFollowStatusManager = new UserTokenFollowManager(
            $em,
            $p
        );

        $response = $tokenFollowStatusManager->getFollowStatus($token, $user);
        $this->assertEquals($expectedResponse, $response);
    }

    public function testGetFollowers(): void
    {
        $token = new Token();
        $token->setSymbol('testTok');

        $followedUser1 = new User();
        $followedUser1->setNickname('1');

        $followedUser2 = new User();
        $followedUser2->setNickname('2');


        $userTokenFollowFollowed1 = new UserTokenFollow();
        $userTokenFollowFollowed1
            ->setUser($followedUser1)
            ->setToken($token)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_FOLLOWED);

        $userTokenFollowFollowed2 = new UserTokenFollow();
        $userTokenFollowFollowed2
            ->setUser($followedUser2)
            ->setToken($token)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_FOLLOWED);

        $em = $this->createMock(EntityManagerInterface::class);
        $utfr = $this->createMock(EntityRepository::class);
        $p = $this->createMock(PublisherInterface::class);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(UserTokenFollow::class)
            ->willReturn($utfr);

        $utfr->expects($this->once())
            ->method('findBy')
            ->with([
                'token' => $token,
                'followStatus' => UserTokenFollow::FOLLOW_STATUS_FOLLOWED,
            ])
            ->willReturn([
                $userTokenFollowFollowed1,
                $userTokenFollowFollowed2,
            ]);

        $tokenFollowStatusManager = new UserTokenFollowManager(
            $em,
            $p
        );

        $followers = $tokenFollowStatusManager->getFollowers($token);
        $this->assertEquals([$followedUser1, $followedUser2], $followers);
    }

    public function testGetFollowedTokens(): void
    {
        $user = new User();
        $user->setNickname('1');

        $followedToken1 = new Token();
        $followedToken1 ->setSymbol('test1');
        $followedToken2 = new Token();
        $followedToken1 ->setSymbol('test2');

        $userTokenFollow1 = new UserTokenFollow();
        $userTokenFollow1
            ->setUser($user)
            ->setToken($followedToken1)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_FOLLOWED);

        $userTokenFollow2 = new UserTokenFollow();
        $userTokenFollow2
            ->setUser($user)
            ->setToken($followedToken2)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_FOLLOWED);

        $em = $this->createMock(EntityManagerInterface::class);
        $utfr = $this->createMock(EntityRepository::class);
        $p = $this->createMock(PublisherInterface::class);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(UserTokenFollow::class)
            ->willReturn($utfr);

        $utfr->expects($this->once())
            ->method('findBy')
            ->with([
                'user' => $user,
                'followStatus' => UserTokenFollow::FOLLOW_STATUS_FOLLOWED,
            ])
            ->willReturn([
                $userTokenFollow1,
                $userTokenFollow2,
            ]);


        $tokenFollowStatusManager = new UserTokenFollowManager(
            $em,
            $p
        );

        $tokens = $tokenFollowStatusManager->getFollowedTokens($user);
        $this->assertEquals([$followedToken1, $followedToken2], $tokens);
    }

    /**
     * @dataProvider isFollowerDataProvider
     */
    public function testIsFollower(
        Token $token,
        User $user,
        ?UserTokenFollow $userTokenFollow,
        bool $expectedResponse
    ): void {
        $em = $this->createMock(EntityManagerInterface::class);
        $utfr = $this->createMock(EntityRepository::class);
        $p = $this->createMock(PublisherInterface::class);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(UserTokenFollow::class)
            ->willReturn($utfr);

        $utfr->expects($this->once())
            ->method('findOneBy')
            ->with([
                'token' => $token,
                'user' => $user,
            ])
            ->willReturn($userTokenFollow);

        $tokenFollowStatusManager = new UserTokenFollowManager(
            $em,
            $p
        );

        $response = $tokenFollowStatusManager->isFollower($user, $token);
        $this->assertEquals($expectedResponse, $response);
    }

    public function manualFollowDataProvider(): array
    {
        return [
            [
                true,
                null,
            ],
            [
                false,
                null,
            ],
            [
                false,
                UserTokenFollow::FOLLOW_STATUS_UNFOLLOWED,
            ],
            [
                false,
                UserTokenFollow::FOLLOW_STATUS_FOLLOWED,
            ],
            [
                false,
                UserTokenFollow::FOLLOW_STATUS_NEUTRAL,
            ],
        ];
    }

    public function getTokenFollowStatusDataProvider(): array
    {
        $token = new Token();
        $user = new User();

        $tokenFollowStatusFollowed = new UserTokenFollow();
        $tokenFollowStatusFollowed
            ->setToken($token)
            ->setUser($user)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_FOLLOWED);

        $tokenFollowStatusUnfollowed = new UserTokenFollow();
        $tokenFollowStatusUnfollowed
            ->setToken($token)
            ->setUser($user)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_UNFOLLOWED);

        $tokenFollowStatusNeutral = new UserTokenFollow();
        $tokenFollowStatusNeutral
            ->setToken($token)
            ->setUser($user)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_NEUTRAL);

        return [
            [
                $token,
                $user,
                $tokenFollowStatusFollowed,
                UserTokenFollow::FOLLOW_STATUS_FOLLOWED,
            ],
            [
                $token,
                $user,
                $tokenFollowStatusUnfollowed,
                UserTokenFollow::FOLLOW_STATUS_UNFOLLOWED,
            ],
            [
                $token,
                $user,
                $tokenFollowStatusNeutral,
                UserTokenFollow::FOLLOW_STATUS_NEUTRAL,
            ],
            [
                $token,
                $user,
                null,
                UserTokenFollow::FOLLOW_STATUS_NEUTRAL,
            ],
        ];
    }

    public function isFollowerDataProvider(): array
    {
        $token = new Token();
        $user = new User();

        $tokenFollowStatusFollowed = new UserTokenFollow();
        $tokenFollowStatusFollowed
            ->setToken($token)
            ->setUser($user)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_FOLLOWED);

        $tokenFollowStatusUnfollowed = new UserTokenFollow();
        $tokenFollowStatusUnfollowed
            ->setToken($token)
            ->setUser($user)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_UNFOLLOWED);

        $tokenFollowStatusNeutral = new UserTokenFollow();
        $tokenFollowStatusNeutral
            ->setToken($token)
            ->setUser($user)
            ->setFollowStatus(UserTokenFollow::FOLLOW_STATUS_NEUTRAL);

        return [
            [
                $token,
                $user,
                $tokenFollowStatusFollowed,
                true,
            ],
            [
                $token,
                $user,
                $tokenFollowStatusUnfollowed,
                false,
            ],
            [
                $token,
                $user,
                $tokenFollowStatusNeutral,
                false,
            ],
            [
                $token,
                $user,
                null,
                false,
            ],
        ];
    }
}
