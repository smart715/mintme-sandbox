<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\Token\Token;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;

class OrderControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testCreatingSellOrder(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', '/api/orders/WEB/'. $tokName . '/pending/page/1');

        $res = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $res['buy']);
        $this->assertCount(1, $res['sell']);
        $this->assertEquals(
            [
                '1.000000000000',
                '1.000000000000',
            ],
            [
                $res['sell'][0]['amount'],
                $res['sell'][0]['price'],
            ]
        );
    }

    public function testCreatingBuyOrder(): void
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
        $this->client->request('GET', '/api/orders/WEB/'. $tokName . '/pending/page/1');
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertCount(0, $res['sell']);
        $this->assertCount(1, $res['buy']);
        $this->assertEquals(
            [
                '0.999000000000',
                '1.000000000000',
            ],
            [
                $res['buy'][0]['amount'],
                $res['buy'][0]['price'],
            ]
        );
    }
}
