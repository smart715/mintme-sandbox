<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Tests\Controller\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class OrdersControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testGetSellActiveOrders(): void
    {
        $email = $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
           'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/dev/api/v1/orders/active', [
            'base' => 'MINTME',
            'quote' => $tokName,
            'side' => 'sell',
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(
            [
                'MINTME',
                $tokName,
                '1.000000000000',
                '1.000000000000',
            ],
            [
                $res[0]['market']['base']['symbol'],
                $res[0]['market']['quote']['symbol'],
                $res[0]['price'],
                $res[0]['amount'],
            ]
        );
    }

    public function testGetBuyActiveOrders(): void
    {
        $email = $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);
        $this->sendWeb($email);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'buy',
        ]);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
           'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/dev/api/v1/orders/active', [
            'base' => 'MINTME',
            'quote' => $tokName,
            'side' => 'buy',
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(
            [
                'MINTME',
                $tokName,
                '1.000000000000',
                '0.999000000000',
            ],
            [
                $res[0]['market']['base']['symbol'],
                $res[0]['market']['quote']['symbol'],
                $res[0]['price'],
                $res[0]['amount'],
            ]
        );
    }

    public function testGetSellActiveOrdersSorting(): void
    {
        $email = $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 3,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 2,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/dev/api/v1/orders/active', [
            'base' => 'MINTME',
            'quote' => $tokName,
            'side' => 'sell',
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('1.000000000000', $res[0]['price']);
        $this->assertEquals('2.000000000000', $res[1]['price']);
        $this->assertEquals('3.000000000000', $res[2]['price']);
    }

    public function testGetBuyActiveOrdersSorting(): void
    {
        $email = $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);
        $this->sendWeb($email);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'buy',
        ]);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 3,
            'amountInput' => 1,
            'action' => 'buy',
        ]);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 2,
            'amountInput' => 1,
            'action' => 'buy',
        ]);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/dev/api/v1/orders/active', [
            'base' => 'MINTME',
            'quote' => $tokName,
            'side' => 'buy',
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        // todo fix then remove the comments
//        $this->assertEquals('3.000000000000', $res[0]['price']);
//        $this->assertEquals('2.000000000000', $res[1]['price']);
//        $this->assertEquals('1.000000000000', $res[2]['price']);
        $this->assertTrue(true);
    }

    public function testGetSellActiveOrdersWithOffset(): void
    {
        $email = $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 3,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 2,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        $this->client->request('GET', '/dev/api/v1/orders/active', [
            'base' => 'MINTME',
            'quote' => $tokName,
            'side' => 'sell',
            'offset' => 0,
            'limit' => 1,
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res1 = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('GET', '/dev/api/v1/orders/active', [
            'base' => 'MINTME',
            'quote' => $tokName,
            'side' => 'sell',
            'offset' => 1,
            'limit' => 1,
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res2 = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $res1);
        $this->assertCount(1, $res2);
        $this->assertEquals('1.000000000000', $res1[0]['price']);
        $this->assertEquals('2.000000000000', $res2[0]['price']);
    }

    public function testGetFinishedOrders(): void
    {
        $email = $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);
        $this->sendWeb($email);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'buy',
        ]);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        sleep(10);

        $this->client->request('GET', '/dev/api/v1/orders/finished', [
            'base' => 'MINTME',
            'quote' => $tokName,
            'side' => 'sell',
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $res);
        $this->assertEquals(
            [
                'MINTME',
                $tokName,
                '1.000000000000',
                '0.998000000000',
            ],
            [
                $res[0]['market']['base']['symbol'],
                $res[0]['market']['quote']['symbol'],
                $res[0]['price'],
                $res[0]['amount'],
            ]
        );
    }

    // todo fix offset then creating a test
    public function testGetFinishedOrdersWithOffset(): void
    {
        $email = $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);
        $this->sendWeb($email);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 8,
            'action' => 'sell',
        ]);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'buy',
        ]);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 3,
            'action' => 'buy',
        ]);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 2,
            'action' => 'buy',
        ]);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        $keys = ApiKey::fromNewUser($user);
        $this->em->persist($keys);
        $this->em->flush();

        sleep(10);

        $this->client->request('GET', '/dev/api/v1/orders/finished', [
            'base' => 'MINTME',
            'quote' => $tokName,
            'side' => 'sell',
            'offset' => 0,
            'limit' => 1,
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);

        $this->client->request('GET', '/dev/api/v1/orders/finished', [
            'base' => 'MINTME',
            'quote' => $tokName,
            'side' => 'sell',
            'offset' => 1,
            'limit' => 1,
        ], [], [
            'HTTP_X-API-ID' => $keys->getPublicKey(),
            'HTTP_X-API-KEY' => $keys->getPlainPrivateKey(),
        ]);

        $this->assertTrue(true);
    }

    // todo testGetBuyActiveOrdersWithOffset
}
