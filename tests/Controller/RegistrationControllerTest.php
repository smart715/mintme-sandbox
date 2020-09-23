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
                'fos_user_registration_form[nickname]' => $this->generateString(),
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
                'fos_user_registration_form[nickname]' => $this->generateString(),
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
                'fos_user_registration_form[nickname]' => $this->generateString(),
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

    public function testRefererRedirect(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/logout');
        $this->client->followRedirect();

        $this->client->request('GET', '/token/' . $tokName . '/trade');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->clickLink('Sign Up');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $email = $this->generateEmail();
        $this->client->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $email,
                'fos_user_registration_form[nickname]' => $this->generateString(),
                'fos_user_registration_form[plainPassword]' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect('/register/confirmed'));
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/token/' . $tokName . '/trade'));
        $this->assertTrue($this->client->getResponse()->isRedirection());
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertContains($tokName, $this->client->getResponse()->getContent());
    }
}
