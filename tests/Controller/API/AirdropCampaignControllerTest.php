<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Tests\Controller\WebTestCase;

class AirdropCampaignControllerTest extends WebTestCase
{
    public function testGetAirdropCampaign(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertNull($res);
    }

    public function testCreateAirdropCampaign(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('+2 days');
        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => [],
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => [],
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertEquals('Token already has active airdrop.', $res['message']);

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNotNull($res);

        $this->assertArrayHasKey('id', $res);
        $this->assertGreaterThan(0, $res['id']);
        $this->assertEquals('200.000000000000', $res['amount']);
        $this->assertEquals(150, $res['participants']);
        $this->assertEquals(0, $res['actualParticipants']);
        $this->assertEquals($endDate->format(\DateTimeImmutable::ATOM), $res['endDate']);
    }

    public function testCreateAirdropCampaignWithInvalidParams(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '0.0099',
            'participants' => 150,
            'actions' => [],
            'actionsData' => [],
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid amount.', $res['message']);

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '10000001',
            'participants' => 200,
            'actions' => [],
            'actionsData' => [],
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid amount.', $res['message']);

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '0.12',
            'participants' => 1227,
            'actions' => [],
            'actionsData' => [],
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
            'actions' => [],
            'actionsData' => [],
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid participants amount.', $res['message']);

        $endDate = new \DateTimeImmutable('-10 minutes');
        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => [],
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid end date.', $res['message']);
    }

    public function testDeleteAirdropCampaign(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('+2 days');
        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => [],
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNotNull($res);
        $this->assertArrayHasKey('id', $res);
        $airdropId = $res['id'];

        $this->client->request('DELETE', '/api/airdrop_campaign/' . $res['id'] . '/delete');

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNull($res);

        $fooClient = self::createClient();
        $this->register($fooClient);
        $fooClient->request('POST', '/api/airdrop_campaign/' . $tokName . '/' . $airdropId . '/claim');
        $this->assertTrue($fooClient->getResponse()->isClientError());
        $res = json_decode((string)$fooClient->getResponse()->getContent(), true);

        $this->assertEquals('Token does not have active airdrop campaign.', $res['message']);
    }

    public function testClaimAirdropCampaign(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '500',
            'participants' => 250,
            'actions' => [],
            'actionsData' => [],
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $airdropId = $res['id'];

        $this->client->request('POST', '/api/airdrop_campaign/' . $tokName . '/' . $airdropId . '/claim');
        $this->assertTrue($this->client->getResponse()->isClientError());

        $fooClient = self::createClient();
        $this->register($fooClient);
        $fooClient->request('POST', '/api/airdrop_campaign/' . $tokName . '/' . $airdropId . '/claim');
        $this->assertTrue($fooClient->getResponse()->isSuccessful());

        $fooClient->request('POST', '/api/airdrop_campaign/' . $tokName . '/' . $airdropId . '/claim');
        $this->assertTrue($fooClient->getResponse()->isClientError());

        $fooClient->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($fooClient->getResponse()->isSuccessful());

        $res = json_decode((string)$fooClient->getResponse()->getContent(), true);
        $this->assertEquals(1, $res['actualParticipants']);
    }
}
