<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfanityControllerTest extends WebTestCase
{
    private const LOCALHOST = 'https://localhost';
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->initDatabase();
    }

    public function testGetCensorConfig(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/api/profanity/getCensorConfig');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        /** @phpstan-ignore-next-line */
        $this->assertArrayHasKey('censorChecks', json_decode($this->client->getResponse()->getContent(), true));
    }

    private function initDatabase(): void
    {
        $kernel = self::$kernel;
        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $metaData = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($metaData);
        $schemaTool->createSchema($metaData);
    }
}
