<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Message\Thread;
use App\Entity\Message\ThreadMetadata;
use App\Entity\User;
use App\Manager\MessageManager;
use App\Repository\MessageMetadataRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MessageManagerTest extends TestCase
{
    /**
     * @dataProvider sendMessageDataProvider
     */
    public function testSendMessage(array $dataSet): void
    {
        $senderId = 1;
        $sender = $this->mockUser();
        $sender
            ->method('getId')
            ->willReturn($senderId);

        $threadMetadata = [];

        foreach ($dataSet as $data) {
            $user = $this->mockUser();
            $user
                ->expects($this->exactly($data['user']['times']))
                ->method($data['user']['method'])
                ->willReturn($data['user']['id']);

            $metadata = $this->mockThreadMetaData();
            $metadata
                ->expects($this->exactly($data['metadata']['times']))
                ->method($data['metadata']['method'])
                ->willReturn($user);

            $threadMetadata[] = $metadata;
        }

        $thread = $this->mockThread();
        $thread
            ->expects($this->once())
            ->method('getMetadata')
            ->willReturn($threadMetadata);

        $em = $this->mockEntityManager(
            $this->mockMessageRepository(),
            $this->mockMessageMetadataRepository()
        );

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $manager = new MessageManager(
            $em,
            $this->mockMessageRepository(),
            $this->mockMessageMetadataRepository()
        );

        $manager->sendMessage($thread, $sender, 'TEST_BODY');
    }

    public function testGetMessage(): void
    {
        $thread = $this->mockThread();
        $participant = $this->mockUser();
        $limit = 10;
        $offset = 0;

        $messageRepository = $this->mockMessageRepository();
        $messageRepository
            ->expects($this->once())
            ->method('getMessages')
            ->with($thread, $participant, $limit, $offset)
            ->willReturn([]);

        $em = $this->mockEntityManager(
            $messageRepository,
            $this->mockMessageMetadataRepository()
        );

        $manager = new MessageManager(
            $em,
            $messageRepository,
            $this->mockMessageMetadataRepository()
        );

        $manager->getMessages($thread, $participant, $limit, $offset);
    }

    public function testGetNewMessage(): void
    {
        $thread = $this->mockThread();
        $lastMessageId = 1;

        $messageRepository = $this->mockMessageRepository();
        $messageRepository
            ->expects($this->once())
            ->method('getNewMessages')
            ->with($thread, $lastMessageId)
            ->willReturn([]);

        $em = $this->mockEntityManager(
            $messageRepository,
            $this->mockMessageMetadataRepository()
        );

        $manager = new MessageManager(
            $em,
            $messageRepository,
            $this->mockMessageMetadataRepository()
        );

        $manager->getNewMessages($thread, $lastMessageId);
    }

    public function testSetRead(): void
    {
        $thread = $this->mockThread();
        $participant = $this->mockUser();

        $messageRepository = $this->mockMessageRepository();
        $messageRepository
            ->expects($this->once())
            ->method('setRead')
            ->with($thread, $participant);

        $em = $this->mockEntityManager(
            $messageRepository,
            $this->mockMessageMetadataRepository()
        );

        $manager = new MessageManager(
            $em,
            $messageRepository,
            $this->mockMessageMetadataRepository()
        );

        $manager->setRead($thread, $participant);
    }

    public function testGetUnreadCount(): void
    {
        $participant = $this->mockUser();

        $withParams = ['participant' => $participant, 'isRead' => false];

        $messageMetaDataRepository = $this->mockMessageMetadataRepository();
        $messageMetaDataRepository
            ->expects($this->once())
            ->method('count')
            ->with($withParams)
            ->willReturn(1);

        $em = $this->mockEntityManager($this->mockMessageRepository(), $messageMetaDataRepository);

        $manager = new MessageManager(
            $em,
            $this->mockMessageRepository(),
            $messageMetaDataRepository,
        );

        $manager->getUnreadCount($participant);
    }

    public function testSetDeleteMessages(): void
    {
        $thread = $this->mockThread();
        $participant = $this->mockUser();

        $messageRepository = $this->mockMessageRepository();
        $messageRepository
            ->expects($this->once())
            ->method('setDeleteMessages')
            ->with($thread, $participant);

        $em = $this->mockEntityManager($messageRepository, $this->mockMessageMetadataRepository());

        $manager = new MessageManager(
            $em,
            $messageRepository,
            $this->mockMessageMetadataRepository()
        );

        $manager->setDeleteMessages($thread, $participant);
    }

    public function sendMessageDataProvider(): array
    {
        return [
            'One participant is sender as well' => [
                'dataSet' => [
                    [
                        'user' => ['id' => 1, 'method' => 'getId', 'times' => 1],
                        'metadata' => ['method' => 'getParticipant', 'times' => 2],
                    ],
                ],
            ],
            'One participant is not sender' => [
                'dataSet' => [
                    [
                        'user' => ['id' => 2, 'method' => 'getId', 'times' => 1],
                        'metadata' => ['method' => 'getParticipant', 'times' => 2],
                    ],
                ],
            ],
            'Two participants and one is sender' => [
                'dataSet' => [
                    [
                        'user' => ['id' => 1, 'method' => 'getId', 'times' => 1],
                        'metadata' => ['method' => 'getParticipant', 'times' => 2],
                    ],
                    [
                        'user' => ['id' => 2, 'method' => 'getId', 'times' => 1],
                        'metadata' => ['method' => 'getParticipant', 'times' => 2],
                    ],
                ],
            ],
            'Two participants and none is sender' => [
                'dataSet' => [
                    [
                        'user' => ['id' => 2, 'method' => 'getId', 'times' => 1],
                        'metadata' => ['method' => 'getParticipant', 'times' => 2],
                    ],
                    [
                        'user' => ['id' => 3, 'method' => 'getId', 'times' => 1],
                        'metadata' => ['method' => 'getParticipant', 'times' => 2],
                    ],
                ],
            ],
        ];
    }

    /** @return MockObject|EntityManagerInterface */
    private function mockEntityManager(
        MessageRepository $messageRepository,
        MessageMetadataRepository $metadataRepository
    ): EntityManagerInterface {
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->method('getRepository')
            ->willReturnOnConsecutiveCalls(
                $messageRepository,
                $metadataRepository,
            );

        return $em;
    }

    /** @return MockObject|Thread */
    private function mockThread(): Thread
    {
        return $this->createMock(Thread::class);
    }

    /** @return MockObject|ThreadMetadata */
    private function mockThreadMetadata(): ThreadMetadata
    {
        return $this->createMock(ThreadMetadata::class);
    }

    /** @return MockObject|User */
    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    /** @return MockObject|MessageRepository */
    private function mockMessageRepository(): MessageRepository
    {
        return $this->createMock(MessageRepository::class);
    }

    /** @return MockObject|MessageMetadataRepository */
    private function mockMessageMetadataRepository(): MessageMetadataRepository
    {
        return $this->createMock(MessageMetadataRepository::class);
    }
}
