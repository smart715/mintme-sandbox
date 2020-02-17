<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Entity\User;
use App\Tests\Controller\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class WebSocketControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testAuthUserWhichNotAuthorized(): void
    {
        $this->register($this->client);

        $this->client->request('GET', '/api/ws/auth');
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $res);
    }

    public function testAuthUserWhichIsAuthorized(): void
    {
        $email = $this->register($this->client);
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        $this->client->request('GET', '/api/ws/auth', [], [], [
            'HTTP_authorization' => $user->getId(),
        ]);
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayNotHasKey('error', $res);
        $this->assertEquals(
            $user->getId(),
            $res['data']['user_id']
        );
    }
}
