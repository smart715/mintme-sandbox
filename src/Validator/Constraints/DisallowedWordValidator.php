<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Manager\TokenManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DisallowedWordValidator extends ConstraintValidator
{
    private const FORBIDDEN_WORDS = ["token", "coin"];
    private TokenManagerInterface $tokenManager;

    public function __construct(
        TokenManagerInterface $tokenManager
    ) {
        $this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param $constraint DisallowedWord
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value || $this->tokenManager->findByName($value)) {
            return;
        }

        array_reduce(
            self::FORBIDDEN_WORDS,
            function ($carry, $item) use ($value, $constraint): void {
                if (1 === preg_match('/\b'.$item. 's?\b/', strtolower($value))) {
                    $this->context->buildViolation($constraint->message)->addViolation();
                }
            }
        );
    }
}
