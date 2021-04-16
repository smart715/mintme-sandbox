<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Tests\Controller\WebTestCase;

class AirdropCampaignControllerTest extends WebTestCase
{
    public function testGetAirdropCampaign(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertNull($res['airdrop']);
        $this->assertNull($res['referral_code']);
    }

    public function testCreateAirdropCampaign(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('+2 days');
        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => [
                'postLink' => true,
                'twitterRetweet' => false,
                'youtubeSubscribe' => false,
                'facebookPage' => false,
            ],
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertIsNumeric($res['id']);

        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => [
                'postLink' => true,
                'twitterRetweet' => false,
                'youtubeSubscribe' => false,
                'facebookPage' => false,
            ],
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertEquals('Token already has active airdrop.', $res['message']);

        $this->client->request('GET', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNotNull($res);

        $airdrop = $res['airdrop'];
        $this->assertArrayHasKey('id', $airdrop);
        $this->assertGreaterThan(0, $airdrop['id']);
        $this->assertEquals('200.000000000000', $airdrop['amount']);
        $this->assertEquals(150, $airdrop['participants']);
        $this->assertEquals(0, $airdrop['actualParticipants']);
        $this->assertEquals($endDate->format(\DateTimeImmutable::ATOM), $airdrop['endDate']);
    }

    public function testCreateAirdropCampaignWithInvalidAmountLow(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '0.0099',
            'participants' => 150,
            'actions' => [
                'postLink' => true,
                'twitterRetweet' => false,
                'youtubeSubscribe' => false,
                'facebookPage' => false,
            ],
            'actionsData' => [],
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid amount.', $res['message']);
    }

    public function testCreateAirdropCampaignWithInvalidAmountHigh(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '10000001',
            'participants' => 200,
            'actions' => [
                'postLink' => true,
                'twitterRetweet' => false,
                'youtubeSubscribe' => false,
                'facebookPage' => false,
            ],
            'actionsData' => [],
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid amount.', $res['message']);
    }

    public function testCreateAirdropCampaignWithInvalidReward(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '0.12',
            'participants' => 1227,
            'actions' => [
                'postLink' => true,
                'twitterRetweet' => false,
                'youtubeSubscribe' => false,
                'facebookPage' => false,
            ],
            'actionsData' => [],
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals(
            'Invalid reward. Set higher amount of tokens for airdrop or lower amount of participants.',
            $res['message']
        );
    }

    public function testCreateAirdropCampaignWithInvalidParticipantAmount(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '0.01',
            'participants' => 99,
            'actions' => [
                'postLink' => true,
                'twitterRetweet' => false,
                'youtubeSubscribe' => false,
                'facebookPage' => false,
            ],
            'actionsData' => [],
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid participants amount.', $res['message']);
    }

    // todo fix then revert back the test
    public function estCreateAirdropCampaignWithInvalidEndDate(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('-10 minutes');
        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => [
                'postLink' => true,
                'twitterRetweet' => false,
                'youtubeSubscribe' => false,
                'facebookPage' => false,
            ],
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid end date.', $res['message']);
    }

    public function testCreateAirdropCampaignWithActionsNotExists(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('+2 days');
        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'endDate' => $endDate->getTimestamp(),
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid actions', $res['message']);
    }

    public function testCreateAirdropCampaignWithWrongAction(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('+2 days');
        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => [
                'twitterMessage' => 'not bool',
                'twitterRetweet' => false,
                'facebookMessage' => false,
                'facebookPage' => false,
                'facebookPost' => false,
                'linkedinMessage' => false,
                'youtubeSubscribe' => false,
                'postLink' => false,
            ],
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid actions', $res['message']);
    }

    public function testCreateAirdropCampaignMissedActions(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('+2 days');
        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => [
                'badName' => false,
            ],
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid actions', $res['message']);
    }

    public function testCreateAirdropCampaignStringActions(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('+2 days');
        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => 'not an array',
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertEquals('Invalid actions', $res['message']);
    }

    public function testDeleteAirdropCampaign(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $endDate = new \DateTimeImmutable('+2 days');
        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '200',
            'participants' => 150,
            'actions' => [
                'postLink' => true,
                'twitterRetweet' => false,
                'youtubeSubscribe' => false,
                'facebookPage' => false,
            ],
            'actionsData' => [],
            'endDate' => $endDate->getTimestamp(),
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNotNull($res);
        $airdrop = $res['airdrop'];
        $this->assertArrayHasKey('id', $airdrop);

        $this->client->request('DELETE', self::LOCALHOST . '/api/airdrop_campaign/' . $airdrop['id'] . '/delete');

        $this->client->request('GET', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $this->assertNull($res['airdrop']);
        $this->assertNull($res['referral_code']);

        $fooClient = self::createClient();
        $this->register($fooClient);
        $fooClient->request(
            'POST',
            self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/' . $airdrop['id'] . '/claim'
        );
        $this->assertTrue($fooClient->getResponse()->isClientError());
        $res = json_decode((string)$fooClient->getResponse()->getContent(), true);

        $this->assertEquals('Token does not have active airdrop campaign.', $res['message']);
    }

    public function testClaimAirdropCampaign(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/create', [
            'amount' => '500',
            'participants' => 250,
            'actions' => [
                'postLink' => true,
                'twitterRetweet' => false,
                'youtubeSubscribe' => false,
                'facebookPage' => false,
            ],
            'actionsData' => [],
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);
        $airdropId = $res['airdrop']['id'];

        $this->client->request(
            'POST',
            self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/' . $airdropId . '/claim'
        );
        $this->assertTrue($this->client->getResponse()->isClientError());

        // todo fix claim_airdrop_action first
//        $fooClient = self::createClient();
//        $this->register($fooClient);
//        $fooClient->request(
//            'POST',
//            self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/' . $airdropId . '/claim',
//            [
//                'postLink' => 'www.google.com',
//            ]
//        );
//        $this->assertTrue($fooClient->getResponse()->isSuccessful());

//        $fooClient->request(
//            'POST',
//            self::LOCALHOST . '/api/airdrop_campaign/' . $tokName . '/' . $airdropId . '/claim'
//        );
//        $this->assertTrue($fooClient->getResponse()->isClientError());
//
//        $fooClient->request('GET', self::LOCALHOST . '/api/airdrop_campaign/' . $tokName);
//        $this->assertTrue($fooClient->getResponse()->isSuccessful());
//
//        $res = json_decode((string)$fooClient->getResponse()->getContent(), true);
//        $this->assertEquals(1, $res['airdrop']['actualParticipants']);
    }
}
