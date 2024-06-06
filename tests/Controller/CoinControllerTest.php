<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CoinControllerTest extends WebTestCase
{
    private const LOCALHOST = 'https://localhost';
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->initDatabase();
    }

    public function testGetTotalUsersRegistered(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/api/coin/total-users-registered');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        //@phpstan-ignore-next-line
        $this->assertArrayHasKey('count', json_decode($this->client->getResponse()->getContent(), true));
    }

    public function testGetTotalWalletsAndTransactions(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/api/coin/total-wallets-and-transactions');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        //@phpstan-ignore-next-line
        $this->assertArrayHasKey('addresses', json_decode($this->client->getResponse()->getContent(), true));
        //@phpstan-ignore-next-line
        $this->assertArrayHasKey('transactions', json_decode($this->client->getResponse()->getContent(), true));
    }

    public function testGetTotalNetworkHashrate(): void
    {
        $this->client->request('POST', self::LOCALHOST . '/api/coin/total-network_hashrate');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        //@phpstan-ignore-next-line
        $this->assertArrayHasKey('hashrate', json_decode($this->client->getResponse()->getContent(), true));
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
