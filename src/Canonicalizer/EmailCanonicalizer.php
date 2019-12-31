<?php declare(strict_types = 1);

namespace App\Canonicalizer;

use FOS\UserBundle\Util\CanonicalizerInterface as FOSUserCanonicalizerInterface;

class EmailCanonicalizer implements FOSUserCanonicalizerInterface
{
    /** @var mixed  */
    private $gmailDomains = ['gmail.com', 'googlemail.com'];

    /** @inheritdoc */
    public function canonicalize($string): string
    {
        if (!$string) {
            return '';
        }

        $domain = substr($string, strrpos($string, '@') + 1);

        if (in_array($domain, $this->gmailDomains)) {
            $name = strstr($string, '@', true);
            $name = str_replace('.', '', strval($name));

            return $name.'@'.$this->gmailDomains[1];
        }

        return $string;
    }
}
