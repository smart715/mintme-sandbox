<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class ApiKeyAuthenticator extends Constraint
{
    /** @var int */
    public $length;

    /** @var bool */
    public $allowNull = false;    // phpcs:ignore

    /** @var string */
    public $message = 'Bad API credentials: "{{ reason }}"';    // phpcs:ignore

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return [
            'length',
            'allowNull',
        ];
    }
}
