<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Sirprize\PostalCodeValidator\Validator as PostalCodeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ZipCodeValidator extends ConstraintValidator
{

    /**
     * {@inheritdoc}
     *
     * @param $value string|null
     * @param $constraint ZipCode
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ZipCode) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ZipCode');
        }

        // if iso code is not specified, try to fetch it via getter from the object, which is currently validated
        if (null === $constraint->iso) {
            $object = $this->context->getObject();
            $getter = $constraint->getter;

            if (!is_callable([$object, $getter])) {
                $objectClass = get_class($object);

                throw new ConstraintDefinitionException(
                    "Method '{$getter}' used as iso code getter does not exist in class '{$objectClass}'"
                );
            }

            $iso = $object->$getter();
        } else {
            $iso = $constraint->iso;
        }

        // ignore empty iso
        if (empty($iso)) {
            return;
        }

        // ignore empty value
        if (!$value) {
            return;
        }

        $validator = new PostalCodeValidator();

        // ignore if iso does not have codes
        if (!$validator->hasCountry($iso)) {
            return;
        }

        if (!$validator->isValid($iso, $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        }
    }
}
