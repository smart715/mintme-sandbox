<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsUrlFromDomain extends Constraint
{
    /** @var string */
    public $domain;

    /** @var string */
    public $message = 'The string "{{ string }}" is not a valid url.';

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'domain';
    }
}
