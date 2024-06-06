<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Entity\Message\Message;
use App\Entity\Message\MessageMetadata;
use App\Entity\Message\Thread;
use App\Entity\User;
use App\Serializer\ThreadNormalizer;
use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ThreadNormalizerTest extends TestCase
{
    private const PARTICIPANT_ID = 10;
    private const SENDER_ID = 1;

    public function testNormalize(): void
    {
        $normalizer = new ThreadNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockTokenStorage(
                $this->mockTokenInterface(true, 2)
            )
        );

        $thread = $this->mockThread();

        $this->assertEquals(
            [
                'lastMessageTimestamp' => 1,
                'hasUnreadMessages' => true,
                'lastMessage' => '',
            ],
            $normalizer->normalize($thread)
        );
    }

    public function testNormalizeWithNoUnreadMessages(): void
    {
        $normalizer = new ThreadNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockTokenStorage(
                $this->mockTokenInterface(true, self::PARTICIPANT_ID)
            )
        );

        $thread = $this->mockThread();

        $this->assertEquals(
            [
                'lastMessageTimestamp' => 1,
                'hasUnreadMessages' => false,
                'lastMessage' => '',
            ],
            $normalizer->normalize($thread)
        );
    }

    public function testNormalizeWhenTheSenderIsTheSameUser(): void
    {
        $normalizer = new ThreadNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockTokenStorage(
                $this->mockTokenInterface(true, self::SENDER_ID)
            )
        );

        $thread = $this->mockThread();

        $this->assertEquals(
            [
                'lastMessageTimestamp' => 1,
                'hasUnreadMessages' => false,
                'lastMessage' => '',
            ],
            $normalizer->normalize($thread)
        );
    }

    public function testNormalizeWithNoUser(): void
    {
        $normalizer = new ThreadNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockTokenStorage(
                $this->mockTokenInterface(false)
            )
        );
        $thread = $this->mockThread();

        $this->assertEquals(
            [
                'lastMessageTimestamp' => 1,
                'hasUnreadMessages' => false,
                'lastMessage' => '',
            ],
            $normalizer->normalize($thread)
        );
    }

    public function testNormalizeWithNoSecurityToken(): void
    {
        $normalizer = new ThreadNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockTokenStorage()
        );
        $thread = $this->mockThread();

        $this->assertEquals(
            [
                'lastMessageTimestamp' => 1,
                'hasUnreadMessages' => false,
                'lastMessage' => '',
            ],
            $normalizer->normalize($thread)
        );
    }

    public function testNormalizeWithNoMessages(): void
    {
        $normalizer = new ThreadNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockTokenStorage(
                $this->mockTokenInterface(true, 2)
            )
        );
        $thread = $this->mockThread(false);

        $this->assertEquals(
            [
                'lastMessageTimestamp' => 1,
                'hasUnreadMessages' => false,
                'lastMessage' => '',
            ],
            $normalizer->normalize($thread)
        );
    }

    public function testSupportsNormalizationSuccess(): void
    {
        $normalizer = new ThreadNormalizer($this->mockObjectNormalizer(), $this->mockTokenStorage());
        $thread = $this->createMock(Thread::class);

        $this->assertTrue($normalizer->supportsNormalization($thread));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $normalizer = new ThreadNormalizer($this->mockObjectNormalizer(), $this->mockTokenStorage());

        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    private function mockObjectNormalizer(): ObjectNormalizer
    {
        $objectNormalizer = $this->createMock(ObjectNormalizer::class);
        $objectNormalizer->method('normalize')->willReturn([]);

        return $objectNormalizer;
    }

    private function mockTokenStorage(?TokenInterface $token = null): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        return $tokenStorage;
    }

    private function mockTokenInterface(bool $isUserInstance = true, int $userId = 1): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')
            ->willReturn($isUserInstance ? $this->mockUser($userId) : $this->mockUserInterface());

        return $token;
    }

    private function mockUser(int $id = 1): User
    {
        $user =  $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function mockUserInterface(): UserInterface
    {
        return $this->createMock(UserInterface::class);
    }

    private function mockThread(bool $hasMessages = true): Thread
    {
        $thread = $this->createMock(Thread::class);
        $thread->method('getCreatedAt')->willReturn($this->mockDateTimeImmutable());
        $thread->method('getMessages')->willReturn($hasMessages ? [
            $this->mockMessage(),
            $this->mockMessage(),
            $this->mockMessage(),
        ] : []);

        return $thread;
    }

    private function mockMessage(): Message
    {
        $message = $this->createMock(Message::class);
        $message->method('getCreatedAt')->willReturn($this->mockDateTimeImmutable());
        $message->method('getSender')->willReturn($this->mockUser(self::SENDER_ID));
        $message->method('getMetadata')->willReturn([$this->mockMessageMetaData()]);

        return $message;
    }

    private function mockDateTimeImmutable(): \DateTimeImmutable
    {
        $time = $this->createMock(\DateTimeImmutable::class);
        $time->method('getTimestamp')->willReturn(1);

        return $time;
    }

    private function mockMessageMetaData(): MessageMetadata
    {
        $metadata = $this->createMock(MessageMetadata::class);
        $metadata->method('getParticipant')->willReturn($this->mockUser(self::PARTICIPANT_ID));
        $metadata->method('isRead')->willReturn(true);

        return $metadata;
    }
}
