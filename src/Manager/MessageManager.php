<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Message\Message;
use App\Entity\Message\MessageMetadata;
use App\Entity\Message\Thread;
use App\Entity\Message\ThreadMetadata;
use App\Entity\User;
use App\Repository\MessageMetadataRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;

class MessageManager implements MessageManagerInterface
{
    private EntityManagerInterface $em;
    private MessageRepository $repository;
    private MessageMetadataRepository $metadataRepository;

    public function __construct(
        EntityManagerInterface $em,
        MessageRepository $messageRepository,
        MessageMetadataRepository $metadataRepository
    ) {
        $this->em = $em;
        $this->repository = $messageRepository;
        $this->metadataRepository = $metadataRepository;
    }

    public function sendMessage(Thread $thread, User $sender, string $body): void
    {
        $message = new Message();
        $message->setSender($sender)
            ->setBody($body)
            ->setThread($thread);

        /** @var ThreadMetadata $threadMetadata */
        foreach ($thread->getMetadata() as $threadMetadata) {
            $messageMetadata = new MessageMetadata();
            $messageMetadata->setParticipant($threadMetadata->getParticipant());
            $messageMetadata->setMessage($message);

            if ($threadMetadata->getParticipant()->getId() === $sender->getId()) {
                $messageMetadata->setRead();
            }

            $message->addMetadata($messageMetadata);
        }

        $this->em->persist($message);
        $this->em->flush();
    }

    public function getMessages(Thread $thread, User $participant, int $limit, int $offset): array
    {
        return $this->repository->getMessages($thread, $participant, $limit, $offset);
    }

    public function getNewMessages(Thread $thread, int $lastMessageId): array
    {
        return $this->repository->getNewMessages($thread, $lastMessageId);
    }

    public function setRead(Thread $thread, User $participant): void
    {
        $this->repository->setRead($thread, $participant);
    }

    public function getUnreadCount(User $participant): int
    {
        return $this->metadataRepository->count([
            'participant' => $participant,
            'isRead' => false,
        ]);
    }

    public function setDeleteMessages(Thread $thread, User $participant): void
    {
        $this->repository->setDeleteMessages($thread, $participant);
    }
}
