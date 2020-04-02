<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\User;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegister(): void
    {
        $email = $this->generateEmail();

        $this->client->request('GET', '/register/');
        $this->client->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $email,
                'fos_user_registration_form[plainPassword]' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect('/register/confirmed'));
        $this->client->followRedirect();

        $this->assertContains(
            'The user has been created successfully.',
            $this->client->getResponse()->getContent()
        );
    }

    public function testRegisterFails(): void
    {
        $str = $this->generateString();

        $this->client->request('GET', '/register/');
        $this->client->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $str,
                'fos_user_registration_form[plainPassword]' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->assertContains(
            'Invalid email address.',
            $this->client->getResponse()->getContent()
        );
    }

    public function testSignUpLanding(): void
    {
        $email = $this->generateEmail();

        $this->client->request('GET', '/sign-up');
        $this->client->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $email,
                'fos_user_registration_form[plainPassword]' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect('/register/confirmed'));
        $this->client->followRedirect();

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $this->assertContains(
            'The user has been created successfully.',
            $this->client->getResponse()->getContent()
        );

        $this->assertEquals('sign-up', $user->getBonus()->getType());
        $this->assertEquals('paid', $user->getBonus()->getStatus());
    }
}
