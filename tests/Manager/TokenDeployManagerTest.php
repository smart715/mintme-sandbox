<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Token\TokenDeploy;
use App\Manager\TokenDeployManager;
use App\Repository\TokenDeployRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenDeployManagerTest extends TestCase
{
    public function testFindByAddress(): void
    {
        $address = 'TEST';
        $tokenDeploy = $this->mockTokenDeploy();

        $tokenDeployRepository = $this->mockTokenDeployRepository();
        $tokenDeployRepository
            ->expects($this->exactly(2))
            ->method('findByAddress')
            ->with($address)
            ->willReturnOnConsecutiveCalls($tokenDeploy, null);

        $tokenDeployManager = new TokenDeployManager($tokenDeployRepository);

        $this->assertEquals($tokenDeploy, $tokenDeployManager->findByAddress($address));
        $this->assertNull($tokenDeployManager->findByAddress($address));
    }

    /** @return MockObject|TokenDeploy */
    private function mockTokenDeploy(): TokenDeploy
    {
        return $this->createMock(TokenDeploy::class);
    }

    /** @return MockObject|TokenDeployRepository*/
    private function mockTokenDeployRepository(): TokenDeployRepository
    {
        return $this->createMock(TokenDeployRepository::class);
    }
}
