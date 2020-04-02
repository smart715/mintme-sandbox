<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class MainDocument extends Constraint
{
    /** @var string  */
    public $message = 'The documents should be added only from Documents tab';
}
