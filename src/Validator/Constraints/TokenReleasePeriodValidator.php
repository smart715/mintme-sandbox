<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Safe\Exceptions\PcreException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class TokenReleasePeriodValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     *
     * @param int[] $value [tokeReleased, releasePeriod]
     * @param $constraint TokenReleasePeriod
     * @throws PcreException
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof TokenReleasePeriod) {
            throw new UnexpectedValueException($constraint, TokenReleasePeriod::class);
        }

        $tokenReleased = $value[0];
        $tokenReleasePeriod = $value[1];

        if (0 === \Safe\preg_match($constraint->validReleasePeriod, (string)$tokenReleasePeriod)) {
            $this->context->buildViolation($constraint->invalidTokenReleasePeriodmessage)
                ->addViolation();
        }

        if ($this->isFullRelease($tokenReleased, $tokenReleasePeriod, $constraint)) {
            $this->validateFullRelease($tokenReleased, $tokenReleasePeriod, $constraint);
        } else {
            $this->validateTokenReleased($tokenReleased, $constraint);
            $this->validateReleasePeriod($tokenReleasePeriod, $constraint);
        }
    }

    private function isFullRelease(
        int $tokenReleased,
        int $tokenReleasePeriod,
        TokenReleasePeriod $constraint
    ): bool {
        $fullTokenReleased = $constraint->fullTokenReleased;
        $fullReleasePeriod = $constraint->fullReleasePeriod;

        return $fullReleasePeriod === $tokenReleasePeriod || $fullTokenReleased === $tokenReleased;
    }

    private function validateFullRelease(
        int $tokenReleased,
        int $tokenReleasePeriod,
        TokenReleasePeriod $constraint
    ): void {
        $fullTokenReleased = $constraint->fullTokenReleased;
        $fullReleasePeriod = $constraint->fullReleasePeriod;

        if ($fullTokenReleased !== $tokenReleased) {
            $this->context->buildViolation($constraint->fullTokenReleaseMessage)
                ->setParameter('{{released}}', (string)$fullTokenReleased)
                ->setParameter('{{period}}', (string)$fullReleasePeriod)
                ->addViolation();
        }

        if ($fullReleasePeriod !== $tokenReleasePeriod) {
            $this->context->buildViolation($constraint->fullTokenReleasePeriodMessage)
                ->setParameter('{{released}}', (string)$fullTokenReleased)
                ->setParameter('{{period}}', (string)$fullReleasePeriod)
                ->addViolation();
        }
    }

    private function validateTokenReleased(int $tokenReleased, TokenReleasePeriod $constraint): void
    {
        $minTokenReleased = $constraint->minTokenReleased;
        $maxTokenReleased = $constraint->maxTokenReleased;

        if ($tokenReleased < $minTokenReleased || $tokenReleased > $maxTokenReleased) {
            $this->context->buildViolation($constraint->tokenReleasemessage)
                ->setParameter('{{min}}', (string)$minTokenReleased)
                ->setParameter('{{max}}', (string)$maxTokenReleased)
                ->addViolation();
        }
    }

    private function validateReleasePeriod(int $tokenReleasePeriod, TokenReleasePeriod $constraint): void
    {
        $minReleasePeriod = $constraint->minReleasePeriod;
        $maxReleasePeriod = $constraint->maxReleasePeriod;

        if ($tokenReleasePeriod < $minReleasePeriod || $tokenReleasePeriod > $maxReleasePeriod) {
            $this->context->buildViolation($constraint->tokenReleasemessage)
                ->setParameter('{{min}}', (string)$minReleasePeriod)
                ->setParameter('{{max}}', (string)$maxReleasePeriod)
                ->addViolation();
        }
    }
}
