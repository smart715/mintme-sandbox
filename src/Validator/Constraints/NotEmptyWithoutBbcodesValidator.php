<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Manager\TokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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
            '/\[\/?(?:b|i|u|s|ul|ol|li|p|s|url|img|h1|h2|h3|h4|h5|h6)*?.*?\]/',
            '',
            $value
        ));

        if ("" === $value) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
