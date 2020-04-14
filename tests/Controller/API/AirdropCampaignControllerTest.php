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
        $this->assertNull($res);
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

        $this->assertArrayHasKey('id', $res);
        $this->assertGreaterThan(0, $res['id']);
        $this->assertEquals('200.000000000000', $res['amount']);
        $this->assertEquals(150, $res['participants']);
        $this->assertEquals(0, $res['actualParticipants']);
        $this->assertEquals($endDate->format(\DateTimeImmutable::ATOM), $res['endDate']);
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
        $this->assertArrayHasKey('id', $res);

        $this->client->request('DELETE', '/api/airdrop_campaign/' . $res['id'] . '/delete');

        $this->client->request('GET', '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNull($res);

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
        $this->assertEquals(1, $res['actualParticipants']);
    }
}
