<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class DateTimeMin extends Constraint
{
    /** @var string */
    public $modify;

    /** @var string */
    public $message = 'Amount must be more than {{modify}} from now.';

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getRequiredOptions()
    {
        return [
            'modify',
        ];
    }
}
