<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\TwitterOAuthException;
use App\Entity\User;
use App\Exception\InvalidTwitterTokenException;
use App\Manager\TwitterManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwitterManagerTest extends TestCase
{
    /** @var User|MockObject */
    private $user;

    public function setUp(): void
    {
        $this->user = $this->mockUser();
    }
    
    /**
     * @dataProvider sendRetweetUserTweetDataProvider
     */
    public function testSendTweetUser(
        bool $isSignedInWithTwitter,
        bool $isPostSuccess,
        ?string $exception,
        ?int $errorCode
    ): void {
        if ($exception) {
            /* @phpstan-ignore-next-line */
            $this->expectException($exception);
        }

        $twitterManager = $this->prepareTwitterManagerForUser($isSignedInWithTwitter, $isPostSuccess, $errorCode);

        $result = $twitterManager->sendTweet($this->user, 'TEST_MESSAGE');

        if (!$exception) {
            $this->assertEquals($result, $twitterManager);
        }
    }

    /**
     * @dataProvider sendRetweetUserTweetDataProvider
     */
    public function testRetweetUser(
        bool $isSignedInWithTwitter,
        bool $isPostSuccess,
        ?string $exception,
        ?int $errorCode
    ): void {
        if ($exception) {
            /* @phpstan-ignore-next-line */
            $this->expectException($exception);
        }

        $twitterManager = $this->prepareTwitterManagerForUser($isSignedInWithTwitter, $isPostSuccess, $errorCode);

        $result = $twitterManager->retweet($this->user, 'TEST_MESSAGE');

        if (!$exception) {
            $this->assertEquals($result, $twitterManager);
        }
    }

    /**
     * @dataProvider sendRetweetGuestTweetDataProvider
     */
    public function testSendTweetGuest(
        bool $validToken,
        bool $validSecret,
        bool $isPostSuccess,
        ?string $exception,
        ?int $errorCode
    ): void {
        if ($exception) {
            /* @phpstan-ignore-next-line */
            $this->expectException($exception);
        }

        $twitterManager = $this->prepareTwitterManagerForGuest($validToken, $validSecret, $isPostSuccess, $errorCode);

        $result = $twitterManager->sendTweet(null, 'TEST_MESSAGE');

        if (!$exception) {
            $this->assertEquals($result, $twitterManager);
        }
    }

    /**
     * @dataProvider sendRetweetGuestTweetDataProvider
     */
    public function testRetweetGuest(
        bool $validToken,
        bool $validSecret,
        bool $isPostSuccess,
        ?string $exception,
        ?int $errorCode
    ): void {
        if ($exception) {
            /* @phpstan-ignore-next-line */
            $this->expectException($exception);
        }

        $twitterManager = $this->prepareTwitterManagerForGuest($validToken, $validSecret, $isPostSuccess, $errorCode);

        $result = $twitterManager->retweet(null, 'TEST_MESSAGE');

        if (!$exception) {
            $this->assertEquals($result, $twitterManager);
        }
    }

    private function prepareTwitterManagerForUser(
        bool $isSignedInWithTwitter,
        bool $isPostSuccess,
        ?int $errorCode
    ): TwitterManager {
        $this->user
            ->expects($this->once())
            ->method('isSignedInWithTwitter')
            ->willReturn($isSignedInWithTwitter);

        $this->user
            ->method('getTwitterAccessToken')
            ->willReturn('TEST_TOKEN');

        $this->user
            ->method('getTwitterAccessTokenSecret')
            ->willReturn('TEST_TOKEN_SECRET');

        $twitter = $this->mockTwitter();
        $twitter
            ->method('post')
            ->willReturn(
                $isPostSuccess
                ? new Response('OK', 200)
                : (object)['errors' => [(object)['code'=> $errorCode,'message'=>'ERROR_MESSAGE']]]
            );

        $logger = $this->mockLogger();
        $session = $this->mockSession();
        $entityManager = $this->mockEntityManager();

        return new TwitterManager(
            $session,
            $twitter,
            $logger,
            $entityManager
        );
    }

    private function prepareTwitterManagerForGuest(
        bool $validToken,
        bool $validSecret,
        bool $isPostSuccess,
        ?int $errorCode
    ): TwitterManager {
        $session = $this->mockSession();

        $session
            ->method('get')
            ->withConsecutive(['twitter_oauth_token'], ['twitter_oauth_token_secret'])
            ->willReturnOnConsecutiveCalls(
                $validToken ? 'TEST_TOKEN' : null,
                $validSecret ? 'TEST_TOKEN_SECRET' : null
            );

        $twitter = $this->mockTwitter();
        $twitter
            ->method('post')
            ->willReturn(
                $isPostSuccess
                ? new Response('OK', 200)
                : (object)['errors' => [(object)['code'=> $errorCode,'message'=>'ERROR_MESSAGE']]]
            );

        $logger = $this->mockLogger();
        $entityManager = $this->mockEntityManager();

        return new TwitterManager(
            $session,
            $twitter,
            $logger,
            $entityManager
        );
    }

    public function sendRetweetGuestTweetDataProvider(): array
    {
        return [
            'Guest and post on twitter is OK' => [
                'validToken' => true,
                'validSecret' => true,
                'isPostSuccess' => true,
                'exception' => null,
                'errorCode' => null,
            ],
            'Guest twitter token invalid' => [
                'validToken' => false,
                'validSecret' => true,
                'isPostSuccess' => true,
                'exception' => 'App\Exception\InvalidTwitterTokenException',
                'errorCode' => null,
            ],
            'Guest twitter secret invalid' => [
                'validToken' => true,
                'validSecret' => false,
                'isPostSuccess' => true,
                'exception' => 'App\Exception\InvalidTwitterTokenException',
                'errorCode' => null,
            ],
            'Guest twitter post NOT success' => [
                'validToken' => true,
                'validSecret' => true,
                'isPostSuccess' => false,
                'exception' => 'App\Exception\InvalidTwitterTokenException',
                'errorCode' => 89,
            ],
            'Guest twitter post already retweeted' => [
                'validToken' => true,
                'validSecret' => true,
                'isPostSuccess' => false,
                'exception' => null,
                'errorCode' => 327,
            ],
            'Guest twitter failed to post with general Exception' => [
                'validToken' => true,
                'validSecret' => true,
                'isPostSuccess' => false,
                'exception' => '\\Exception',
                'errorCode' => null,
            ],
        ];
    }

    public function sendRetweetUserTweetDataProvider(): array
    {
        return [
            'User twitter logged in and post on twitter OK' => [
                'isSignedInWithTwitter' => true,
                'isPostSuccess' => true,
                'exception' => null,
                'errorCode' => null,
            ],
            'User twitter NOT logged in' => [
                'isSignedInWithTwitter' => false,
                'isPostSuccess' => true,
                'exception' => 'App\Exception\InvalidTwitterTokenException',
                'errorCode' => null,
            ],
            'User twitter logged in BUT post failed' => [
                'isSignedInWithTwitter' => true,
                'isPostSuccess' => false,
                'exception' => 'App\Exception\InvalidTwitterTokenException',
                'errorCode' => 89,
            ],
            'User twitter logged in BUT already retweeted' => [
                'isSignedInWithTwitter' => true,
                'isPostSuccess' => false,
                'exception' => null,
                'errorCode' => 327,
            ],
            'User twitter logged in BUT post failed with general Exception' => [
                'isSignedInWithTwitter' => true,
                'isPostSuccess' => false,
                'exception' => '\\Exception',
                'errorCode' => null,
            ],
        ];
    }

    /**
     * @return User|MockObject
     */
    private function mockUser()
    {
        return $this->createMock(User::class);
    }

    /**
     * @return SessionInterface|MockObject
     */
    private function mockSession()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return TwitterOAuth|MockObject
     */
    private function mockTwitter()
    {
        return $this->createMock(TwitterOAuth::class);
    }

    /**
     * @return LoggerInterface|MockObject
     */
    private function mockLogger()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return EntityManagerInterface|MockObject
     */
    private function mockEntityManager()
    {
        return $this->createMock(EntityManagerInterface::class);
    }
}
