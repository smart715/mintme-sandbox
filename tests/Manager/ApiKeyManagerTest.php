<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\ApiKey;
use App\Manager\ApiKeyManager;
use App\Repository\ApiKeyRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApiKeyManagerTest extends TestCase
{
    public function testFindApiKey(): void
    {
        $keyValue = 'TEST';
        $apiKey = $this->mockApiKey();

        $apiKeyRepository = $this->mockApiKeyRepository();
        $apiKeyRepository
            ->expects($this->exactly(2))
            ->method('findApiKey')
            ->with($keyValue)
            ->willReturnOnConsecutiveCalls($apiKey, null);

        $apiKeyManager = new ApiKeyManager($apiKeyRepository);

        $this->assertEquals($apiKey, $apiKeyManager->findApiKey($keyValue));
        $this->assertNull($apiKeyManager->findApiKey($keyValue));
    }

    /** @return MockObject|ApiKey */
    private function mockApiKey(): ApiKey
    {
        return $this->createMock(ApiKey::class);
    }

    /** @return MockObject|ApiKeyRepository */
    private function mockApiKeyRepository(): ApiKeyRepository
    {
        return $this->createMock(ApiKeyRepository::class);
    }
}
