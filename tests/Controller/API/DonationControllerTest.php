<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Tests\Controller\WebTestCase;

class DonationControllerTest extends WebTestCase
{
    public function testCheckDonation(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/donation/WEB/' . $tokName . '/check/50.50/1');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', '/api/donation/BTC/' . $tokName . '/check/0.00055/1');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testMakeDonation(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/donation/WEB/' . $tokName . '/make', [
            'amount' => '1000',
            'fee' => 1,
            'expected_count_to_receive' => 10,
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('POST', '/api/donation/BTC/' . $tokName . '/make', [
            'amount' => '0.000145',
            'fee' => 1,
            'expected_count_to_receive' => '0.0002',
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
}
