<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NotEmptyWithoutBbcodesValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     *
     * @param $constraint NotEmptyWithoutBbcodes
     */
    public function validate($value, Constraint $constraint): void
    {
        $value = trim(preg_replace(
            '/\[\s*\/?\s*(?:b|i|u|s|ul|ol|li|p|s|url|img|h1|h2|h3|h4|h5|h6)\s*\]/',
            '',
            $value
        ));

        if ("" === $value) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
