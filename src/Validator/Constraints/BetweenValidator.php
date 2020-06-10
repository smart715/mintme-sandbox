<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class BetweenValidator extends ConstraintValidator
{
    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(MoneyWrapperInterface $moneyWrapper)
    {
        $this->moneyWrapper = $moneyWrapper;
    }

    /**
     * {@inheritDoc}
     *
     * @param $constraint Between
     */
    public function validate($value, Constraint $constraint): void
    {
        $currency = $value->getCurrency()->getCode();
        $min = (string)$constraint->min;
        $max = (string)$constraint->max;

        $minObj = $this->moneyWrapper->parse($min, $currency);
        $maxObj = $this->moneyWrapper->parse($max, $currency);

        if ($value->lessThan($minObj) || $value->greaterThan($maxObj)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{min}}', $min)
                ->setParameter('{{max}}', $max)
                ->addViolation();
        }
    }
}
