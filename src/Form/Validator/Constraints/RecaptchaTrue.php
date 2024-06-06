<?php declare(strict_types = 1);

namespace App\Form\Validator\Constraints;

use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue;

/**
 * @Annotation
 * @Target("PROPERTY")
 * @codeCoverageIgnore
 */
class RecaptchaTrue extends IsTrue
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function validatedBy(): string
    {
        return 'app_recaptcha.true';
    }
}
