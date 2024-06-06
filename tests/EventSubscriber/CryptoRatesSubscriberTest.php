<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\Crypto;
use App\Events\CryptoRatesRefreshedEvent;
use App\EventSubscriber\CryptoRatesSubscriber;
use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CryptoRatesSubscriberTest extends TestCase
{
    public function testOnCryptoRatesRefreshed(): void
    {
        $cm = $this->mockCryptoManager();
        $em = $this->mockEntityManager();

        $crs = new CryptoRatesSubscriber($cm, $em);

        $event = new CryptoRatesRefreshedEvent([
            Symbols::BTC => [
                Symbols::USD => 1000,
            ],
        ]);

        $crs->onCryptoRatesRefreshed($event);
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn(Symbols::BTC);

        $cm = $this->createMock(CryptoManagerInterface::class);
        $cm->expects($this->once())->method('findAll')->willReturn([$crypto]);

        return $cm;
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        return $em;
    }
}
