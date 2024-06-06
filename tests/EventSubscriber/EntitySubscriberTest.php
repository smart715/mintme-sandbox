<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\EntitySubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreFlushEventArgs;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class EntitySubscriberTest extends TestCase
{
    public function testViewOnlyTrue(): void
    {
        $ent = new EntitySubscriber($this->mockSession(true), $this->mockLogger());
        $args = $this->mockArgs(true);
        $ent->preFlush($args);
    }

    public function testViewOnlyFalse(): void
    {
        $ent = new EntitySubscriber($this->mockSession(false), $this->mockLogger());
        $args = $this->mockArgs(false);
        $ent->preFlush($args);
    }

    private function mockSession(bool $view): SessionInterface
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturn($view);

        return $session;
    }

    private function mockArgs(bool $view): PreFlushEventArgs
    {
        $args = $this->createMock(PreFlushEventArgs::class);
        $args->method('getEntityManager')->willReturn($this->mockEntityManager($view));

        return $args;
    }

    private function mockEntityManager(bool $called): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($called ? $this->atLeastOnce() : $this->never())->method('clear');

        return $em;
    }

    private function mockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }
}
