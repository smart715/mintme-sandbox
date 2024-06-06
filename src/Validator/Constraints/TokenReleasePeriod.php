<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class TokenReleasePeriod extends Constraint
{

    /** @var string */
    public $validReleasePeriod;
    /** @var int */
    public $minReleasePeriod;
    /** @var int */
    public $maxReleasePeriod;
    /** @var int */
    public $fullReleasePeriod;

    /** @var int */
    public $minTokenReleased;
    /** @var int */
    public $maxTokenReleased;
    /** @var int */
    public $fullTokenReleased;

    // phpcs:ignore
    public string $invalidTokenReleasePeriodmessage = 'only 1,2,3,5,10,15,20,30,40,50 are valid values for release period';
    // phpcs:ignore
    public string $tokenReleasemessage = 'The token release period must be between {{min}} and {{max}}';
    // phpcs:ignore
    public string $fullTokenReleaseMessage = 'The release period cannot be {{period}} if {{released}}% of the tokens are not released.';
    // phpcs:ignore
    public string $fullTokenReleasePeriodMessage = '{{released}}% tokens release is only possible when release period is set to {{period}}.';

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return [
            'validReleasePeriod',
            'minReleasePeriod',
            'maxReleasePeriod',
            'fullReleasePeriod',
            'minTokenReleased',
            'maxTokenReleased',
            'fullTokenReleased',
        ];
    }
}
