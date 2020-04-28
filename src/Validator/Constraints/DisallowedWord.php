<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class DisallowedWord extends Constraint
{
    /** @var string */
    public $message = 'Token name cannot contain "token" or "coin" words.';
}
