<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * @Annotation
 */
class IsUrlFromDomain extends Constraint
{
    /** @var string */
    public $domain;

    /** @var string */
    public $message = 'The string "{{ string }}" is not a valid {{ domain }} url.';

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'domain';
    }
}
