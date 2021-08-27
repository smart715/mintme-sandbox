<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;
use App\Utils\Symbols;

class DonationControllerTest extends WebTestCase
{
    public function testCheckDonationInvalidAmountWEB(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request(
            'GET',
            '/api/donate/' . Symbols::WEB . '/' . $tokName . '/check/' . Symbols::WEB . '/0.000099'
        );

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid donation amount.', $res['message']);
    }

    public function testCheckDonationInvalidAmountBTC(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request(
            'GET',
            '/api/donate/' . Symbols::WEB . '/' . $tokName . '/check/' . Symbols::BTC . '/0.00000099'
        );

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid donation amount.', $res['message']);
    }

    // todo fix then revert
    public function estCheckDonationLowAmountWEB(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request(
            'GET',
            '/api/donate/' . Symbols::WEB . '/' . $tokName . '/check/' . Symbols::WEB . '/20'
        );

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid donation amount.', $res['message']);
    }

    // todo fix then revert
    public function estCheckDonationLowAmountBTC(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request(
            'GET',
            '/api/donate/' . Symbols::WEB . '/' . $tokName . '/check/' . Symbols::BTC . '/0.00055'
        );

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid donation amount.', $res['message']);
    }

    public function testCheckDonationSuccessWEB(): void
    {
        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->sendWeb($email);
        $this->client->request(
            'GET',
            '/api/donate/' . Symbols::WEB . '/' . $tokName . '/check/' . Symbols::WEB . '/20'
        );

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('amountToReceive', $res);
    }

    public function testCheckDonationSuccessBTC(): void
    {
        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->deposit($email, '150000', Symbols::BTC);
        $this->client->request(
            'GET',
            '/api/donate/' . Symbols::WEB . '/' . $tokName . '/check/' . Symbols::BTC . '/0.00055'
        );

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('amountToReceive', $res);
    }

    public function testMakeDonationInvalidAmountWEB(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/donate/' . Symbols::WEB . '/' . $tokName . '/make', [
            'currency' => Symbols::WEB,
            'amount' => '0.00001',
            'expected_count_to_receive' => 3,
        ]);

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid donation amount.', $res['message']);
    }

    public function testMakeDonationInvalidAmountBTC(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/donate/' . Symbols::WEB . '/' . $tokName . '/make', [
            'currency' => Symbols::BTC,
            'amount' => '0.0000001',
            'expected_count_to_receive' => 2,
        ]);

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid donation amount.', $res['message']);
    }

    public function testMakeDonationAvailabilityChangedWEB(): void
    {
        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->sendWeb($email);
        $this->client->request('POST', '/api/donate/' . Symbols::WEB . '/' . $tokName . '/make', [
            'currency' => Symbols::WEB,
            'amount' => '40',
            'expected_count_to_receive' => 10,
        ]);

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Tokens availability changed. Please adjust donation amount.',
            $res['message']
        );
    }

    public function testMakeDonationAvailabilityChangedBTC(): void
    {
        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->deposit($email, '150000', Symbols::BTC);
        $this->client->request('GET', '/api/cryptos/' . Symbols::BTC . '/balance');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('0.00150000', $res);

        $this->client->request('POST', '/api/donate/' . Symbols::WEB . '/' . $tokName . '/make', [
            'currency' => Symbols::BTC,
            'amount' => $res,
            'expected_count_to_receive' => '0.0001',
        ]);

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Tokens availability changed. Please adjust donation amount.',
            $res['message']
        );
    }

    public function testMakeDonationSuccessWEB(): void
    {
        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->sendWeb($email);
        $this->client->request('POST', '/api/donate/' . Symbols::WEB . '/' . $tokName . '/make', [
            'currency' => Symbols::WEB,
            'amount' => '40',
            'expected_count_to_receive' => 0,
        ]);

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNull($res);
    }

    public function testMakeDonationSuccessBTC(): void
    {
        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->deposit($email, '150000', Symbols::BTC);
        $this->client->request('GET', '/api/cryptos/' . Symbols::BTC . '/balance');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('0.00150000', $res);

        $this->client->request('POST', '/api/donate/' . Symbols::WEB . '/' . $tokName . '/make', [
            'currency' => Symbols::BTC,
            'amount' => $res,
            'expected_count_to_receive' => 0,
        ]);

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNull($res);
    }
}
