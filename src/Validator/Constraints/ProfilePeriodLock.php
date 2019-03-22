<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class ProfilePeriodLock extends Constraint
{
    /** @var string */
    public $message = 'This field can be changed at the end of "{{ date }}".';
}
