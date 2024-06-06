<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Blacklist\Blacklist;
use App\Manager\BlacklistManager;
use App\Repository\BlacklistRepository;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BlacklistManagerTest extends TestCase
{
    public function testDomainBlacklisted(): void
    {
        /** @var BlacklistRepository|MockObject */
        $repository = $this->mockBlacklistRepository();
        $repository
            ->expects($this->once())
            ->method('matchValue')
            ->with('example.com', Blacklist::AIRDROP_DOMAIN, true)
            ->willReturn(true);

        /** @var EntityManagerInterface|MockObject */
        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(Blacklist::class)
            ->willReturn($repository);

        $blacklistManager = new BlacklistManager(
            $entityManager,
            $this->mockPhoneNumberUtil(),
        );

        $isBlacklisted = $blacklistManager->isBlacklistedAirdropDomain('https://example.com/', true);
        $this->assertTrue($isBlacklisted);

        $isBlacklisted = $blacklistManager->isBlacklistedAirdropDomain('someinvalidurl.com/', true);
        $this->assertTrue($isBlacklisted);
    }

    public function testDomainNotBlacklisted(): void
    {
        /** @var BlacklistRepository|MockObject */
        $repository = $this->mockBlacklistRepository();
        $repository
            ->expects($this->once())
            ->method('matchValue')
            ->with('example.com', Blacklist::AIRDROP_DOMAIN, true)
            ->willReturn(false);

        /** @var EntityManagerInterface|MockObject */
        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(Blacklist::class)
            ->willReturn($repository);

        $blacklistManager = new BlacklistManager(
            $entityManager,
            $this->mockPhoneNumberUtil(),
        );

        $isBlacklisted = $blacklistManager->isBlacklistedAirdropDomain('https://example.com/', true);
        $this->assertFalse($isBlacklisted);
    }

    public function testBlacklistedEmail(): void
    {
        /** @var BlacklistRepository|MockObject */
        $repository = $this->mockBlacklistRepository();
        $repository
            ->expects($this->once())
            ->method('matchValue')
            ->with('example.com', Blacklist::EMAIL, true)
            ->willReturn(true);

        /** @var EntityManagerInterface|MockObject */
        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(Blacklist::class)
            ->willReturn($repository);

        $blacklistManager = new BlacklistManager(
            $entityManager,
            $this->mockPhoneNumberUtil(),
        );

        $isBlacklisted = $blacklistManager->isBlackListedEmail('user@example.com', true);
        $this->assertTrue($isBlacklisted);
    }

     /**
     * @dataProvider blacklistProvider
     */
    public function testBlacklistedToken(
        string $token,
        bool $isBlacklisted,
        string $blacklistValue,
        string $blacklistType
    ): void {
        /** @var Blacklist|MockObject */
        $blacklist = $this->mockBlacklist();

        $blacklist->method('getValue')->willReturn($blacklistValue);
        $blacklist->method('getType')->willReturn($blacklistType);

        /** @var BlacklistRepository|MockObject */
        $repository = $this->mockBlacklistRepository();
        $repository
            ->expects($this->exactly(4))
            ->method('findBy')
            ->willReturn([
                $blacklist,
            ]);

        $repository
            ->method('matchValue')
            ->willReturn($isBlacklisted);

        /** @var EntityManagerInterface|MockObject */
        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(Blacklist::class)
            ->willReturn($repository);

        $blacklistManager = new BlacklistManager(
            $entityManager,
            $this->mockPhoneNumberUtil(),
        );

        $result = $blacklistManager->isBlacklistedToken($token);

        $this->assertEquals($isBlacklisted, $result);
    }

    public function testBulkAdd(): void
    {
        /** @var BlacklistRepository|MockObject */
        $repository = $this->mockBlacklistRepository();

        /** @var EntityManagerInterface|MockObject */
        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(Blacklist::class)
            ->willReturn($repository);

        $entityManager
            ->expects($this->exactly(10))
            ->method('persist');

        $entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        $entityManager
            ->expects($this->exactly(2))
            ->method('clear');

        $blacklistManager = new BlacklistManager(
            $entityManager,
            $this->mockPhoneNumberUtil(),
        );

        $values=\SplFixedArray::fromArray(array_fill(0, 10, 'token'));

        $blacklistManager->bulkAdd($values->toArray(), 'crypto-symbol', 10);
    }

    public function blacklistProvider(): array
    {
        return [
            ['token1', false, 'token', 'crypto-symbol'],
            ['token2', true, 'token', 'crypto-symbol'],
            ['token', true, 'token', 'something1'],
            ['token firstmatch', false, 'token', 'something'],
            ['token token', true, 'token', 'something'],
        ];
    }

    private function mockBlacklist(): Blacklist
    {
        return $this->createMock(Blacklist::class);
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    private function mockPhoneNumberUtil(): PhoneNumberUtil
    {
        return $this->createMock(PhoneNumberUtil::class);
    }

    private function mockBlacklistRepository(): BlacklistRepository
    {
        return $this->createMock(BlacklistRepository::class);
    }
}
