<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class EntitySubscriber implements EventSubscriber
{
    private SessionInterface $session;
    private LoggerInterface $logger;

    public function __construct(SessionInterface $session, LoggerInterface $logger)
    {
        $this->session = $session;
        $this->logger = $logger;
    }

    public function getSubscribedEvents(): array
    {
        return [
            'preFlush',
        ];
    }

    public function preFlush(PreFlushEventArgs $args): void
    {
        try {
            if ($this->session->get('view_only_mode')) {
                $entityManager = $args->getEntityManager();
                $entityManager->clear();
            }
        } catch (\Throwable $ex) {
            $this->logger->error(
                'Something went wrong in preFlush event handler in EntitySubscriber.',
                [
                    'message' => $ex->getMessage(),
                    'trace' => $ex->getTrace(),
                ]
            );
        }
    }
}
