<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Tests\Controller\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class UsersControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testCreateApiKeys(): void
    {
        $email = $this->register($this->client);

        $this->client->request('POST', '/api/users/keys');
        $res = json_decode($this->client->getResponse()->getContent(), true);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
           'email' => $email,
        ]);

        $this->assertArrayHasKey('publicKey', $res);
        $this->assertArrayHasKey('plainPrivateKey', $res);
        $this->assertEquals($res['publicKey'], $user->getApiKey()->getPublicKey());
        $this->assertEquals('', $user->getApiKey()->getPlainPrivateKey());
    }

    public function testGetApiKeys(): void
    {
        $email = $this->register($this->client);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/api/users/keys');
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('publicKey', $res);
        $this->assertEquals('', $res['plainPrivateKey']);
    }
}
