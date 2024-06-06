<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class ValidationCodeLimits extends Constraint
{
    /** @var string */
    public $dailyLimit;
    
    /** @var string */
    public $weeklyLimit;
    
    /** @var string */
    public $monthlyLimit;

    /** @var string */
    public $messageType;

    public ?string $message = '{{message}}'; // phpcs:ignore

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return [
            'dailyLimit',
            'weeklyLimit',
            'monthlyLimit',
        ];
    }
}
