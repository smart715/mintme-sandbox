<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Manager\AirdropReferralCodeManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AirdropReferralCodeManagerTest extends TestCase
{
    /** @dataProvider encodeHashDataProvider */
    public function testEncodeHash(int $id, string $hash): void
    {
        $arcm = new AirdropReferralCodeManager($this->mockEntityManager());

        $this->assertEquals($hash, $arcm->encodeHash($id));
        $this->assertEquals($id, $arcm->decodeHash($arcm->encodeHash($id)));
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
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($this->createMock(ObjectRepository::class));

        return $em;
    }
}
