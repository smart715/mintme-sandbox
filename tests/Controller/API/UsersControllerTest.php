<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Tests\Controller\WebTestCase;

class UsersControllerTest extends WebTestCase
{
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
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('publicKey', $res);
        $this->assertEquals('', $res['plainPrivateKey']);
    }

    public function testCreateApiKeys(): void
    {
        $email = $this->register($this->client);

        $this->client->request('POST', '/api/users/keys');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
           'email' => $email,
        ]);

        $this->assertArrayHasKey('publicKey', $res);
        $this->assertArrayHasKey('plainPrivateKey', $res);
        $this->assertEquals($res['publicKey'], $user->getApiKey()->getPublicKey());
        $this->assertEquals('', $user->getApiKey()->getPlainPrivateKey());
    }

    public function testInvalidateApiKeys(): void
    {
        $email = $this->register($this->client);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('DELETE', '/api/users/keys');

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $this->assertNull($user->getApiKey());
    }

    public function testCreateApiClient(): void
    {
        $email = $this->register($this->client);

        $this->client->request('POST', '/api/users/clients');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $this->assertArrayHasKey('id', $res);
        $this->assertArrayHasKey('secret', $res);
        $this->assertEquals($res['id'], $user->getApiClients()[0]['id']);
        $this->assertArrayNotHasKey('secret', $user->getApiClients()[0]);
    }

    public function testDeleteApiClient(): void
    {
        $email = $this->register($this->client);

        $this->client->request('POST', '/api/users/clients');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        $this->assertCount(1, $user->getApiClients());

        $this->client->request('DELETE', '/api/users/clients/' . $res['id']);

        $this->em->refresh($user);
        $this->assertCount(0, $user->getApiClients());
    }
}
