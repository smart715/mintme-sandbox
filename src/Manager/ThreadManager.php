<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Message\Thread;
use App\Entity\Message\ThreadMetadata;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Repository\ThreadRepository;
use Doctrine\ORM\EntityManagerInterface;

class ThreadManager implements ThreadManagerInterface
{
    private ThreadRepository $repository;

    private EntityManagerInterface $em;

    public function __construct(ThreadRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    public function find(int $id): ?Thread
    {
        return $this->repository->find($id);
    }

    public function delete(int $id): void
    {
        $thread = $this->repository->find($id);
        $this->em->remove($thread);
        $this->em->flush();
    }

    public function firstOrNewDMThread(Token $token, User $trader): Thread
    {
        $subQb = $this->em->createQueryBuilder();
        $subQb = $subQb->select('IDENTITY(md.thread)')
            ->from(ThreadMetadata::class, 'md')
            ->where($subQb->expr()->in('md.participant', [$token->getOwner()->getId(), $trader->getId()]))
            ->groupBy('md.thread')
            ->having('COUNT(md.thread) > 1');

        $qb = $this->repository->createQueryBuilder('t');
        $thread = $qb
            ->where('t.type = :type')
            ->andWhere($qb->expr()->in('t.id', $subQb->getDQL()))
            ->setParameter('type', Thread::TYPE_DM)
            ->getQuery()
            ->getResult()[0] ?? null;

        if (!$thread) {
            $thread = new Thread();
            $thread->setToken($token);
            $thread->setType(Thread::TYPE_DM);
            $thread->addMetadata(
                (new ThreadMetadata())
                    ->setParticipant($token->getOwner())
                    ->setThread($thread)
            );
            $thread->addMetadata(
                (new ThreadMetadata())
                    ->setParticipant($trader)
                    ->setThread($thread)
            );

            $this->em->persist($thread);
            $this->em->flush();
            $this->em->refresh($token);
        }

        return $thread;
    }

    public function traderThreads(User $trader): Array
    {
        $qb = $this->repository->createQueryBuilder('t');

        return $qb->join('t.metadata', 'md')
            ->where('md IS NOT NULL')
            ->andWhere('md.isHidden = 0')
            ->andWhere('md.participant = :participant')
            ->setParameter('participant', $trader)
            ->getQuery()
            ->getResult();
    }

    public function toggleBlockUser(array $threadMetadata, User $participant): void
    {
        /** @var ThreadMetadata $item */
        foreach ($threadMetadata as $item) {
            if ($item->getParticipant()->getId() === $participant->getId()) {
                $item->setIsBlocked(!$item->getIsBlocked());
                $this->em->flush();

                break;
            }
        }
    }

    public function toggleHiddenThread(array $threadMetadata, User $participant): void
    {
        /** @var ThreadMetadata $item */
        foreach ($threadMetadata as $item) {
            if ($item->getParticipant()->getId() === $participant->getId()) {
                $item->setHidden(!$item->isHidden());
                $this->em->flush();

                break;
            }
        }
    }

    public function showHiddenThread(array $threadMetadata, User $participant): void
    {
        $hidenTreads = array_filter($threadMetadata, fn ($item) => $item->isHidden());

        $this->toggleHiddenThread($hidenTreads, $participant);
    }

    public function areAllThreadsHidden(array $threadMetadata): bool
    {
        /** @var ThreadMetadata $item */
        foreach ($threadMetadata as $item) {
            if (!$item->isHidden()) {
                return false;
            }
        }

        return true;
    }
}
