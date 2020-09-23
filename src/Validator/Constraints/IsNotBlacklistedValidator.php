<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Manager\BlacklistManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsNotBlacklistedValidator extends ConstraintValidator
{
    /** @var BlacklistManagerInterface */
    private $blacklistManager;

    public function __construct(BlacklistManagerInterface $blacklistManager)
    {
        $this->blacklistManager = $blacklistManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param $constraint IsNotBlacklisted
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $name = trim($value);
        $blacklist = $this->blacklistManager->getList("token");

        foreach ($blacklist as $blist) {
            if (false !== strpos(strtolower($name), strtolower($blist->getValue()))
                && (strlen($name) - strlen($blist->getValue())) <= 1) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
        }

        if ($this->blacklistManager->isBlacklisted($value, $constraint->type, $constraint->caseSensetive)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
