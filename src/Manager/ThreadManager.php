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
    /** @var ThreadRepository */
    private $repository;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        /** @var ThreadRepository $repository */
        $repository = $em->getRepository(Thread::class);
        $this->repository = $repository;
    }

    public function find(int $id): ?Thread
    {
        return $this->repository->find($id);
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
           ->andWhere('md.participant = :participant')
           ->setParameter('participant', $trader)
           ->getQuery()
           ->getResult();
    }
}
