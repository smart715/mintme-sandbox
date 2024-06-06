<?php declare(strict_types = 1);

namespace App\Tests\Services\JwtService;

use App\Services\JwtService\JwtService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JwtServiceTest extends WebTestCase
{
    public function testSussefulTokenCreated(): void
    {
        $jwtService = $this->getJwtServiceInstance();

        $token = $jwtService->createToken(['test' => 'test']);
        $this->assertEquals(3, count(explode('.', $token)));
    }

    private function getJwtServiceInstance(): JwtService
    {
        $client = static::createClient();

        return new JwtService(
            $client->getKernel()->getContainer()->getParameter('coinify_pem_file'),
            $client->getKernel()->getContainer()->getParameter('coinify_pem_passphrase')
        );
    }
}
