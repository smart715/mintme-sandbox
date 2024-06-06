<?php declare(strict_types = 1);

namespace App\Tests\Consumer\Helpers;

use App\Consumers\Helpers\DBConnection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

class DBConnectionTest extends TestCase
{
    public function testReconnectIfDisconnected(): void
    {
        $em = $this->mockEM(true, $this->never(), $this->never());
        DBConnection::reconnectIfDisconnected($em);
    }

    public function testReconnectIfDisconnectedIfNotConnected(): void
    {
        $em = $this->mockEM(false, $this->once(), $this->once());
        DBConnection::reconnectIfDisconnected($em);
    }

    private function mockEM(
        bool $isConnected,
        InvokedCount $closeInv,
        InvokedCount $connectInv
    ): EntityManagerInterface {
        $connection = $this->createMock(Connection::class);

        $connection->expects($closeInv)->method('close');
        $connection->expects($connectInv)->method('connect');
        $connection->expects($this->once())->method('ping')->willReturn($isConnected);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        return $em;
    }
}
