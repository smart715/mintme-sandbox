<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/** @codeCoverageIgnore */
class EmailTransformer implements DataTransformerInterface
{
    /** @var mixed  */
    private $gmailDomains = ['gmail.com', 'googlemail.com'];

    /** @inheritdoc */
    public function transform($value): string
    {
        return $value ?? '';
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return $this->gmailEmailHandler($value);
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
