<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class IsNotBlacklisted extends Constraint
{
    /** @var string */
    public $type;

    /** @var bool */
    public $caseSensetive = true;

    /** @var string */
    public $message = 'This value is blacklisted';
}
