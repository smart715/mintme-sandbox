<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Tests\Controller\WebTestCase;

class OrdersControllerTest extends WebTestCase
{
    public function testCreatingSellOrder(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);
        $this->client->request('GET', '/api/orders/WEB/'. $tokName . '/pending/page/1');

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $res['buy']);
        $this->assertCount(1, $res['sell']);
        $this->assertCount(0, $res['totalBuyOrders']);
        $this->assertCount(1, $res['totalSellOrders']);
        $this->assertEquals(
            [
                '1.000000000000',
                '1.000000000000',
                '1.000000000000',
            ],
            [
                $res['sell'][0]['amount'],
                $res['sell'][0]['price'],
                $res['totalSellOrders'],
            ]
        );
    }

    public function testCreatingBuyOrder(): void
    {
        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);
        $this->sendWeb($email);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'buy',
        ]);
        $this->client->request('GET', '/api/orders/WEB/'. $tokName . '/pending/page/1');

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertCount(0, $res['sell']);
        $this->assertCount(1, $res['buy']);
        $this->assertCount(1, $res['totalBuyOrders']);
        $this->assertCount(0, $res['totalSellOrders']);
        $this->assertEquals(
            [
                '0.998000000000',
                '1.000000000000',
                '1.000000000000',
            ],
            [
                $res['buy'][0]['amount'],
                $res['buy'][0]['price'],
                $res['totalBuyOrders'],
            ]
        );
    }

    public function testCancelOrder(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('GET', '/api/orders/WEB/'. $tokName . '/pending/page/1');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $res['sell']);

        $this->client->request('POST', '/api/orders/cancel/WEB/'. $tokName, [
            'orderData' => [
                $res['sell'][0]['id'],
            ],
        ]);

        $this->client->request('GET', '/api/orders/WEB/'. $tokName . '/pending/page/1');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $res['sell']);
    }

    // todo check then revert
    public function estExecutedOrders(): void
    {
        $email = $this->register($this->client);
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

        sleep(15);

        $this->client->request('GET', '/api/orders/WEB/'. $tokName . '/executed/last/0');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $res);
    }

    public function testExecutedUserOrders(): void
    {
        $email = $this->register($this->client);
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

        $this->client->request('GET', '/api/orders/executed/page/1');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $res);
    }

    public function testPendingUserOrders(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('GET', '/api/orders/pending/page/1');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $res);
    }

    public function testPendingOrderDetails(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('GET', '/api/orders/WEB/'. $tokName . '/pending/page/1');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->client->request('GET', '/api/orders/WEB/'. $tokName . '/pending/' . $res['sell'][0]['id']);

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertEquals($tokName, $res['market']['quote']['name']);
        $this->assertEquals('pending', $res['status']);
    }
}
