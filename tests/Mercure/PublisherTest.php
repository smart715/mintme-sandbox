<?php declare(strict_types = 1);

namespace App\Tests\Mercure;

use _HumbugBox196d2b78600b\Nette\Neon\Exception;
use App\Mercure\Publisher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PublisherTest extends TestCase
{
    public function testPublish(): void
    {
        $n = $this->createMock(NormalizerInterface::class);
        $n->expects($this->once())
            ->method('normalize')
            ->with('bar', null, ['groups' => ['API']])
            ->willReturn('bar');

        $bp = $this->createMock(PublisherInterface::class);
        $bp->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->callback(
                    fn (Update $u) => in_array('foo', $u->getTopics()) && '"bar"' === $u->getData()
                )
            );

        $p = new Publisher($bp, $n, $this->createMock(LoggerInterface::class));
        $p->publish('foo', 'bar');
    }

    public function testPublishNormalizerException(): void
    {
        $n = $this->createMock(NormalizerInterface::class);
        $n->method('normalize')->willThrowException(new \Exception());

        $l = $this->createMock(LoggerInterface::class);
        $l->expects($this->once())->method('error');

        $p = new Publisher($this->createMock(PublisherInterface::class), $n, $l);
        $p->publish('foo', 'bar');
    }

    public function testPublishJsonException(): void
    {
        $n = $this->createMock(NormalizerInterface::class);
        $n->method('normalize')->willReturn(NAN);

        $l = $this->createMock(LoggerInterface::class);
        $l->expects($this->once())->method('error');

        $p = new Publisher($this->createMock(PublisherInterface::class), $n, $l);
        $p->publish('foo', 'bar');
    }

    public function testPublishPublisherError(): void
    {
        $n = $this->createMock(NormalizerInterface::class);
        $n->method('normalize')->willReturn('bar');

        $bp = $this->createMock(PublisherInterface::class);
        $bp->method('__invoke')->willThrowException(new \Exception());

        $l = $this->createMock(LoggerInterface::class);
        $l->expects($this->once())->method('error');

        $p = new Publisher($bp, $n, $l);
        $p->publish('foo', 'bar');
    }
}
