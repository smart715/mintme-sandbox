<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\ApiKeyAuthenticator;
use PHPUnit\Framework\TestCase;

class ApiKeyAuthenticatorTest extends TestCase
{
    public function testGetRequiredOptions(): void
    {
        $apiKeyAuthenticator = new ApiKeyAuthenticator([
            'length' => 64,
            'allowNull' => false,
        ]);
        $this->assertEquals(
            ['length', 'allowNull'],
            $apiKeyAuthenticator->getRequiredOptions()
        );
    }
}
