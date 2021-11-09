<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\User;

class ResettingControllerTest extends WebTestCase
{
    public function testSendEmailAction(): void
    {
        $email = $this->register($this->client);

        $this->client->request('GET', self::LOCALHOST . '/resetting/request');

        $this->client->submitForm(
            'Change password',
            [
                'username' => $email,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $this->client->followRedirect();

        $this->assertNotNull($user->getConfirmationToken());

        $this->assertContains(
            'Email Sent!',
            $this->client->getResponse()->getContent()
        );
    }

    public function testResetActionNotFound(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/resetting/reset/' . $this->generateString());
        $this->assertContains(
            'Page not found',
            $this->client->getResponse()->getContent()
        );
    }

    public function testResetAction(): void
    {
        $fooClient = self::createClient();
        $fooEmail = $this->register($fooClient);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $fooEmail,
        ]);
        $user->setConfirmationToken($this->generateString());
        $user->setPasswordRequestedAt(new \DateTime());
        $this->em->persist($user);
        $this->em->flush();

        $this->client->request('GET', self::LOCALHOST . '/resetting/reset/' . $user->getConfirmationToken());

        $this->client->submitForm(
            'Change password',
            [
                'app_user_resetting[plainPassword]' => 'NewFoo123',
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->client->followRedirect();

        $this->assertContains(
            'The password has been reset successfully.',
            $this->client->getResponse()->getContent()
        );
    }
}
