<?php declare(strict_types = 1);

namespace App\Tests\Consumer\Helpers;

use App\Consumers\Helpers\DBConnection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\TestCase;

class DBConnectionTest extends TestCase
{
    public function testReconnectIfDisconnected(): void
    {
        DBConnection::reconnectIfDisconnected($this->mockEM(true, $this->never(), $this->never()));
    }

    public function testReconnectIfDisconnectedIfNotConnected(): void
    {
        DBConnection::reconnectIfDisconnected($this->mockEM(false, $this->once(), $this->once()));
    }

    private function mockEM(
        bool $isConnected,
        Invocation $closeInv,
        Invocation $connectInv
    ): EntityManagerInterface {
        $connection = $this->createMock(Connection::class);

        if ($isConnected) {
            $connection->method('executeQuery')->willReturn(
                $this->createMock(ResultStatement::class)
            );
        } else {
            $connection->method('executeQuery')->willThrowException(new DBALException());
        }

        $connection->expects($closeInv)->method('close');
        $connection->expects($connectInv)->method('connect');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        return $em;
    }
}
