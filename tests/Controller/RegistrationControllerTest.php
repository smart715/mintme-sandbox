<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\User;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegister(): void
    {
        $email = $this->generateEmail();

        $this->client->request('GET', self::LOCALHOST . '/register/');
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

        $this->assertStringContainsString(
            'The user has been created successfully.',
            (string)$this->client->getResponse()->getContent()
        );
    }

    public function testRegisterFails(): void
    {
        $str = $this->generateString();

        $this->client->request('GET', self::LOCALHOST . '/register/');
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

        $this->assertStringContainsString(
            'Invalid email address.',
            (string)$this->client->getResponse()->getContent()
        );
    }

    public function testSignUpLanding(): void
    {
        $email = $this->generateEmail();

        $this->client->request('GET', self::LOCALHOST . '/sign-up');
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

        $this->assertStringContainsString(
            'The user has been created successfully.',
            (string)$this->client->getResponse()->getContent()
        );

        $this->assertEquals('sign-up', $user->getBonus()->getType());
        $this->assertEquals('paid', $user->getBonus()->getStatus());
    }

    public function testRefererRedirect(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = self::createClient();

        $fooClient->request('GET', self::LOCALHOST . '/token/' . $tokName . '/trade');
        $this->assertTrue($fooClient->getResponse()->isSuccessful());

        $fooClient->clickLink('Sign Up');
        $this->assertTrue($fooClient->getResponse()->isSuccessful());

        $email = $this->generateEmail();
        $fooClient->submitForm(
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

        $this->assertTrue($fooClient->getResponse()->isRedirect('/register/confirmed'));
        $fooClient->followRedirect();
        $this->assertTrue($fooClient->getResponse()->isRedirect(self::LOCALHOST . '/token/' . $tokName . '/trade'));
        $this->assertTrue($fooClient->getResponse()->isRedirection());
        $fooClient->followRedirect();
        $this->assertTrue($fooClient->getResponse()->isSuccessful());
        $this->assertStringContainsString($tokName, (string)$fooClient->getResponse()->getContent());
    }
}
