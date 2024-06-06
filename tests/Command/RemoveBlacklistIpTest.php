<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\RemoveBlacklistIp;
use App\Config\BlacklistIpConfig;
use App\Entity\Blacklist\BlacklistIp;
use App\Manager\BlacklistIpManagerInterface;
use Doctrine\ORM\AbstractQuery as Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RemoveBlacklistIpTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        ?string $address,
        bool $isBlacklistIpExist,
        int $invokedCount,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new RemoveBlacklistIp(
                $this->mockBlacklistIpManager($isBlacklistIpExist, $invokedCount),
                $this->mockBlacklistIpConfig($address)
            )
        );

        $command = $application->find('app:remove-blacklisted-ip');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'address' => $address,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'address is not valid will return an error and status code equals 1' => [
                'address' => '127',
                'isBlacklistIpExist' => false,
                'invokedCount' => 0,
                'expected' => 'IP address is not valid',
                'statusCode' => 1,
            ],
            'address is set and isBlacklistIpExist is set to false will return an error and status code equals 1' => [
                'address' => '127.0.0.1',
                'isBlacklistIpExist' => false,
                'invokedCount' => 0,
                'expected' => 'IP address not found',
                'statusCode' => 1,
            ],
            'address is set and isBlacklistIpExist is set to true will return a success and status code equals 0' => [
                'address' => '127.0.0.1',
                'isBlacklistIpExist' => true,
                'invokedCount' => 1,
                'expected' => 'IP address "127.0.0.1" has been deleted',
                'statusCode' => 0,
            ],
            'address is not set will return a success and status code equals 0' => [
                'address' => null,
                'isBlacklistIpExist' => true,
                'invokedCount' => 2,
                'expected' => '2 IP(s) have been deleted',
                'statusCode' => 0,
            ],
        ];
    }

    private function mockBlacklistIpManager(
        bool $isBlacklistIpExist,
        int $invokedCount
    ): BlacklistIpManagerInterface {
        $blacklistIpManager = $this->createMock(BlacklistIpManagerInterface::class);
        $blacklistIpManager
            ->method('getBlackListIpByAddress')
            ->willReturn($isBlacklistIpExist ? $this->mockBlacklistIp() : null);
        $blacklistIpManager
            ->method('getBlackListIpByNumberOfDaysQueryBuilder')
            ->willReturn($this->mockQueryBuilder($invokedCount));
        $blacklistIpManager
            ->expects($this->exactly($invokedCount))
            ->method('deleteBlacklistIp');

        return $blacklistIpManager;
    }

    private function mockBlacklistIpConfig(?string $address): BlacklistIpConfig
    {
        $blacklistIpConfig = $this->createMock(BlacklistIpConfig::class);
        $blacklistIpConfig
            ->expects($address ? $this->never() : $this->once())
            ->method('getDays')
            ->willReturn(5);

        return $blacklistIpConfig;
    }

    private function mockBlacklistIp(): BlacklistIp
    {
        $blacklistIp = $this->createMock(BlacklistIp::class);
        $blacklistIp
            ->method('getAddress')
            ->willReturn('127.0.0.1');

        return $blacklistIp;
    }

    private function mockQueryBuilder(int $invokedCount): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->method('getQuery')
            ->willReturn($this->mockQuery($invokedCount));

        return $queryBuilder;
    }

    private function mockQuery(int $invokedCount): Query
    {
        $query = $this->createMock(Query::class);
        $query
            ->method('iterate')
            ->willReturnOnConsecutiveCalls(
                array_fill(0, $invokedCount, $this->mockBlacklistIp()),
            );

        return $query;
    }
}
