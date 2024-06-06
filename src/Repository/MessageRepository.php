<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Message\Message;
use App\Entity\Message\MessageMetadata;
use App\Entity\Message\Thread;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function getMessages(Thread $thread, User $participant, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('m');

        return $qb->join('m.metadata', 'md')
            ->where('m.thread = :threadId')
            ->andWhere('md.participant = :participant')
            ->andWhere('md.isDeleted = :isDeleted')
            ->setParameter('threadId', $thread->getId())
            ->setParameter('participant', $participant)
            ->setParameter('isDeleted', false)
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getResult();
    }

    public function setDeleteMessages(Thread $thread, User $participant): void
    {

        $subQb = $this->createQueryBuilder('message')
            ->select('m.id')
            ->from(Message::class, 'm')
            ->where('m.thread = :thread');

        $qb = $this->createQueryBuilder('messageMetadata');
        $qb->update(MessageMetadata::class, 'md')
            ->set('md.isDeleted', true)
            ->where('md.participant = :participant')
            ->andWhere(
                $qb->expr()->in('md.message', $subQb->getDQL())
            )
            ->setParameter('participant', $participant)
            ->setParameter('thread', $thread)
            ->getQuery()
            ->execute();
    }

    public function getNewMessages(Thread $thread, int $lastMessageId): array
    {
        $qb = $this->createQueryBuilder('m');

        return $qb->where('m.thread = :threadId')
            ->andWhere('m.id > :lastId')
            ->setParameter('threadId', $thread->getId())
            ->setParameter('lastId', $lastMessageId)
            ->getQuery()
            ->getResult();
    }

    public function setRead(Thread $thread, User $participant): void
    {
        $subQb = $this->createQueryBuilder('m')
            ->select('m.id')
            ->where('m.thread = :thread');

        $qb = $this->createQueryBuilder('md');
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
}
