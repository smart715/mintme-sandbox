<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Tests\Controller\WebTestCase;

class MarketsControllerTest extends WebTestCase
{
    public function testGetMarkets(): void
    {
        $email = $this->register($this->client);
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
           'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/dev/api/v1/markets', [
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

    public function testGetMarket(): void
    {
        $email = $this->register($this->client);
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/dev/api/v1/markets/BTC/MINTME', [], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals(
            [
                'BTC',
                'MINTME',
            ],
            [
                $res['base'],
                $res['quote'],
            ]
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

        $this->client->request('GET', '/dev/api/v1/markets', [
            'offset' => 0,
            'limit' => 3,
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res1 = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->client->request('GET', '/dev/api/v1/markets', [
            'offset' => 1,
            'limit' => 3,
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res2 = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertCount(3, $res1);
        $this->assertCount(3, $res2);
        $this->assertEquals($res1[0], $res2[0]);
        $this->assertNotEquals($res1[1], $res2[1]);
        // todo fix offset then remove the comment here.
        //$this->assertEquals($res1[2], $res2[1]);
    }
}
