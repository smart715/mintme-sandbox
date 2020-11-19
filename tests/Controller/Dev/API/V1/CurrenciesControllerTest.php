<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V1;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Tests\Controller\WebTestCase;

class CurrenciesControllerTest extends WebTestCase
{
    public function testGetCurrencies(): void
    {
        $email = $this->register($this->client);
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
           'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/dev/api/v1/currencies', [
            'offset' => 0,
            'limit' => 100,
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertGreaterThan(
            1,
            json_decode((string)$this->client->getResponse()->getContent(), true)
        );
    }

    public function testGetCurrency(): void
    {
        $email = $this->register($this->client);
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/dev/api/v1/currencies/MINTME', [], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals(
            'MINTME',
            json_decode((string)$this->client->getResponse()->getContent(), true)['symbol']
        );
    }

    public function testGetCurrenciesWithLimitAndOffset(): void
    {
        $email = $this->register($this->client);
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
           'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/dev/api/v1/currencies', [
            'offset' => 0,
            'limit' => 1,
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res1 = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->client->request('GET', '/dev/api/v1/currencies', [
            'offset' => 1,
            'limit' => 1,
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res2 = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $res1);
        $this->assertCount(1, $res2);
        $this->assertNotEquals($res1, $res2);
    }
}
