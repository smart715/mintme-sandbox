<?php declare(strict_types = 1);

namespace App\Security\Providers;

use FOS\UserBundle\Security\UserProvider;

class UserEmailProvider extends UserProvider
{
    /** @var mixed  */
    protected $gmailDomains = ['gmail.com', 'googlemail.com'];

    /**
     * {@inheritdoc}
     */
    protected function findUser($username)
    {
        $username = $this->gmailEmailHandler($username);

        return $this->userManager->findUserByUsernameOrEmail($username);
    }

    /** @inheritdoc */
    protected function gmailEmailHandler($email): string
    {
        if (!$email) {
            $email = '';
        }

        $domain = substr($email, strrpos($email, '@') + 1);

        if (in_array($domain, $this->gmailDomains)) {
            $name = strstr($email, '@', true);
            $name = str_replace('.', '', strval($name));

            return $name.'@'.$domain;
        }

        return $email;
    }
}
