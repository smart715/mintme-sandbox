<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @Annotation
 */
class ZipCode extends Constraint
{
    /** @var string */
    public $message = 'This value is not a valid ZIP code.';

    /** @var string */
    public $iso;

    /** @var string */
    public $getter;

    public function __construct(?array $options = null)
    {
        parent::__construct($options);

        if (null === $this->iso && null === $this->getter) {
            throw new MissingOptionsException(
                "Either the option 'iso' or 'getter' must be given for constraint 'App\Validator\Constraints\ZipCode'",
                ['iso', 'getter']
            );
        }
    }
}
