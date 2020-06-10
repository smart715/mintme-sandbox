<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class UniqueNickname extends Constraint
{
    /** @var string */
    public $message = 'Nickname already exists.';
}
