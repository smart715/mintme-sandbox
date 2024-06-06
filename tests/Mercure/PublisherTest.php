<?php declare(strict_types = 1);

namespace App\Tests\Mercure;

use App\Entity\TradableInterface;
use App\Entity\User;
use App\Mercure\Publisher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
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

        $bp = $this->createMock(HubInterface::class);
        $bp->expects($this->once())
            ->method('publish')
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

        $p = new Publisher($this->createMock(HubInterface::class), $n, $l);
        $p->publish('foo', 'bar');
    }

    public function testPublishJsonException(): void
    {
        $n = $this->createMock(NormalizerInterface::class);
        $n->method('normalize')->willReturn(NAN);

        $l = $this->createMock(LoggerInterface::class);
        $l->expects($this->once())->method('error');

        $p = new Publisher($this->createMock(HubInterface::class), $n, $l);
        $p->publish('foo', 'bar');
    }

    public function testPublishPublisherError(): void
    {
        $n = $this->createMock(NormalizerInterface::class);
        $n->method('normalize')->willReturn('bar');

        $bp = $this->createMock(HubInterface::class);
        $bp->method('publish')->willThrowException(new \Exception());

        $l = $this->createMock(LoggerInterface::class);
        $l->expects($this->once())->method('error');

        $p = new Publisher($bp, $n, $l);
        $p->publish('foo', 'bar');
    }

    public function testPublishWithdrawEvent(): void
    {
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock
            ->method('normalize')
            ->willReturnCallback(fn ($x) => $x);

        $hubMock = $this->createMock(HubInterface::class);
        $hubMock
            ->expects($this->once())
            ->method('publish')
            ->with(
                $this->callback(
                    function (Update $update) {
                        $this->assertEquals(['withdraw/1'], $update->getTopics());
                        $this->assertEquals('{"tradable":"WEB"}', $update->getData());
                        $this->assertTrue($update->isPrivate());

                        return true;
                    }
                )
            );

        $loggerMock = $this->createMock(LoggerInterface::class);

        $publisher = new Publisher($hubMock, $normalizerMock, $loggerMock);

        $userMock = $this->createMock(User::class);
        $userMock->method('getId')->willReturn(1);

        $tradable = $this->createMock(TradableInterface::class);
        $tradable->method('getSymbol')->willReturn('WEB');

        $publisher->publishWithdrawEvent($userMock, $tradable);
    }
}
