<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropReferralCode;
use App\Entity\User;
use App\Manager\AirdropReferralCodeManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AirdropReferralCodeManagerTest extends TestCase
{
    /** @dataProvider encodeHashDataProvider */
    public function testEncodeHash(int $id, string $hash): void
    {
        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->mockObjectRepository());

        $arcm = new AirdropReferralCodeManager($entityManager);

        $this->assertEquals($hash, $arcm->encodeHash($id));
    }

    /** @dataProvider encodeHashDataProvider */
    public function testDecodeHash(int $id, string $hash): void
    {
        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->mockObjectRepository());

        $arcm = new AirdropReferralCodeManager($entityManager);

        $this->assertEquals($id, $arcm->decodeHash($arcm->encodeHash($id)));
    }

    /** @dataProvider encodeHashDataProvider */
    public function testEncode(int $id, string $hash): void
    {
        $airdropReferralCode = $this->mockAirdropReferralCode();
        $airdropReferralCode
            ->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        
        $entityManager = $this->mockEntityManager();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->mockObjectRepository());

        $arcm = new AirdropReferralCodeManager($entityManager);

        $this->assertEquals($hash, $arcm->encode($airdropReferralCode));
    }


    /** @dataProvider encodeHashDataProvider */
    public function testDecode(int $id, string $hash): void
    {
        $airdropReferralCode = $this->mockAirdropReferralCode();

        $objectRepository = $this->mockObjectRepository();
        $objectRepository
            ->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($airdropReferralCode);
        
        $entityManager = $this->mockEntityManager();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($objectRepository);

        $arcm = new AirdropReferralCodeManager($entityManager);

        $this->assertEquals($airdropReferralCode, $arcm->decode($hash));
    }


    public function testByAirdropAndUser(): void
    {
        $airdrop = $this->mockAirdrop();
        $user = $this->mockUser();

        $airdropReferralCode = $this->mockAirdropReferralCode();

        $objectRepository = $this->mockObjectRepository();
        $objectRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['airdrop' => $airdrop, 'user' => $user])
            ->willReturn($airdropReferralCode);
        
        $entityManager = $this->mockEntityManager();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($objectRepository);

        $arcm = new AirdropReferralCodeManager($entityManager);

        $this->assertEquals($airdropReferralCode, $arcm->getByAirdropAndUser($airdrop, $user));
    }

    public function testCreate(): void
    {
        $airdrop = $this->mockAirdrop();
        $user = $this->mockUser();

        $objectRepository = $this->mockObjectRepository();
        
        $entityManager = $this->mockEntityManager();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($objectRepository);

        $entityManager
            ->expects($this->once())
            ->method('persist');

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $arcm = new AirdropReferralCodeManager($entityManager);

        $result = $arcm->create($airdrop, $user);

        $this->assertTrue($result instanceof AirdropReferralCode);
        $this->assertSame($result->getUser(), $user);
        $this->assertSame($result->getAirdrop(), $airdrop);
    }

    public function encodeHashDataProvider(): array
    {
        return [
            [1, 'E'],
            [393692, 'GAdw'],
            [225279, 'Db_8'],
            [729010, 'LH7I'],
            [647110, 'J38Y'],
            [199575, 'DC5c'],
            [249710, 'Dz24'],
            [381762, 'F00I'],
            [540386, 'IPuI'],
            [900045, 'Nu80'],
            [236141, 'Dmm0'],
        ];
    }

    /**
     * @return EntityManagerInterface|MockObject
     */
    private function mockEntityManager()
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @return EntityRepository|MockObject
     */
    private function mockObjectRepository()
    {
        return $this->createMock(EntityRepository::class);
    }

    /**
     * @return AirdropReferralCode|MockObject
     */
    private function mockAirdropReferralCode()
    {
        return $this->createMock(AirdropReferralCode::class);
    }

    /**
     * @return Airdrop|MockObject
     */
    private function mockAirdrop()
    {
        return $this->createMock(Airdrop::class);
    }

    /**
     * @return User|MockObject
     */
    private function mockUser()
    {
        return $this->createMock(User::class);
    }
}
