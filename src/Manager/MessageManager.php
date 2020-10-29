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
    /** @var EntityManagerInterface */
    private $em;

    /** @var MessageRepository */
    private $repository;

    /** @var MessageMetadataRepository */
    private $metadataRepository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        /** @var MessageRepository $repository */
        $repository = $em->getRepository(Message::class);
        $this->repository = $repository;

        /** @var MessageMetadataRepository $metadataRepository */
        $metadataRepository = $em->getRepository(MessageMetadata::class);
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

    public function getMessages(Thread $thread, int $limit, int $offset): array
    {
        return $this->repository->findBy(
            [
                'thread' => $thread->getId(),
            ],
            [
                'id' => 'ASC',
            ],
            $limit,
            $offset
        );
    }

    public function getNewMessages(Thread $thread, int $lastMessageId): array
    {
        $qb = $this->repository->createQueryBuilder('m');

        return $qb->where('m.thread = :threadId')
            ->andWhere('m.id > :lastId')
            ->setParameter('threadId', $thread->getId())
            ->setParameter('lastId', $lastMessageId)
            ->getQuery()
            ->getResult();
    }

    public function setRead(Thread $thread, User $participant): void
    {
        $subQb = $this->em->createQueryBuilder()
            ->select('m.id')
            ->from(Message::class, 'm')
            ->where('m.thread = :thread');

        $qb = $this->em->createQueryBuilder();
        $qb->update(MessageMetadata::class, 'md')
            ->set('md.isRead', true)
            ->where('md.participant = :participant')
            ->andWhere(
                $qb->expr()->in('md.message', $subQb->getDQL())
            )
            ->setParameter('participant', $participant)
            ->setParameter('thread', $thread)
            ->getQuery()
            ->execute();
    }

    public function getUnreadCount(User $participant): int
    {
        return $this->metadataRepository->count([
            'participant' => $participant,
            'isRead' => false,
        ]);
    }
}
