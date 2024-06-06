<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\Token\Token;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TokenDescriptionValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     *
     * @param $constraint TokenDescription
     */
    public function validate($value, Constraint $constraint): void
    {
        /** @var Token $token */
        $token = $this->context->getObject();

        $descLength = strlen((string)$value);
        $min = $constraint->min;
        $max = $constraint->max;

        if ($token->isCreatedOnMintmeSite() && (($min > $descLength) || $max < $descLength)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{min}}', (string)$min)
                ->setParameter('{{max}}', (string)$max)
                ->addViolation();
        }
    }
}
