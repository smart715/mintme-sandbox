<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Tests\Controller\WebTestCase;

class AirdropCampaignControllerTest extends WebTestCase
{
    public function testGetAirdropCampaign(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertNull($res['airdrop']);
        $this->assertEquals(0.01, $res['airdropParams']['min_tokens_amount']);
        $this->assertEquals(100, $res['airdropParams']['min_participants_amount']);
        $this->assertEquals(999999, $res['airdropParams']['max_participants_amount']);
        $this->assertEquals(0.0001, $res['airdropParams']['min_token_reward']);
    }

    public function testCreateAirdropCampaign(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('+2 days');
        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'endDate' => $endDate->getTimestamp(),
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNotNull($res);

        $this->assertArrayHasKey('airdrop', $res);
        $this->assertArrayHasKey('airdropParams', $res);
        $this->assertGreaterThan(0, $res['airdrop']['id']);
        $this->assertEquals('200.000000000000', $res['airdrop']['amount']);
        $this->assertEquals(150, $res['airdrop']['participants']);
        $this->assertEquals(0, $res['airdrop']['actualParticipants']);
        $this->assertEquals($endDate->format(\DateTimeImmutable::ATOM), $res['airdrop']['endDate']);
    }

    public function testCreateAirdropCampaignWithInvalidParams(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '0.0099',
            'participants' => 150,
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid amount.', $res['message']);

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '10000001',
            'participants' => 200,
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid amount.', $res['message']);

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '0.12',
            'participants' => 1227,
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals(
            'Invalid reward. Set higher amount of tokens for airdrop or lower amount of participants.',
            $res['message']
        );

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '0.01',
            'participants' => 99,
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid participants amount.', $res['message']);

        $endDate = new \DateTimeImmutable('-10 minutes');
        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'endDate' => $endDate->getTimestamp(),
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid end date.', $res['message']);
    }

    public function testDeleteAirdropCampaign(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('+2 days');
        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'endDate' => $endDate->getTimestamp(),
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNotNull($res);
        $this->assertArrayHasKey('airdrop', $res);
        $this->assertArrayHasKey('id', $res['airdrop']);
        $this->assertArrayHasKey('airdropParams', $res);

        $this->client->request('DELETE', '/api/airdrop_campaign/' . $res['airdrop']['id'] . '/delete');

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNull($res['airdrop']);

        $this->client->request('GET', '/logout');
        $this->client->followRedirect();

        $this->register($this->client);
        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/claim');
        $this->assertTrue($this->client->getResponse()->isClientError());
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertEquals('Token does not have active airdrop campaign.', $res['message']);
    }

    public function testClaimAirdropCampaign(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '500',
            'participants' => 250,
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/claim');
        $this->assertTrue($this->client->getResponse()->isClientError());

        $this->client->request('GET', '/logout');
        $this->client->followRedirect();

        $this->register($this->client);
        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/claim');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $res['airdrop']['actualParticipants']);
    }
}
