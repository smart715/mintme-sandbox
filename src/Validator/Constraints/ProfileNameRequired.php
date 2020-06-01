<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ProfileNameRequired extends Constraint
{
    /** @var string */
    public $firstNameMessage = 'First Name have to be filled whether the last name is filled';

    /** @var string */
    public $lastNameMessage = 'Last Name have to be filled whether the first name is filled';
}
