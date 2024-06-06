<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V1;

use App\Exception\ApiNotFoundException;
use App\Utils\Symbols;
use FOS\RestBundle\Controller\AbstractFOSRestController;

abstract class DevApiController extends AbstractFOSRestController
{
    private const DISALLOWED_VALUES = [
        Symbols::WEB,
    ];

    protected function checkForDisallowedValues(string $base, ?string $quote = null): void
    {
        if (in_array(mb_strtoupper($base), self::DISALLOWED_VALUES)
            || in_array(mb_strtoupper($quote ?? ''), self::DISALLOWED_VALUES)) {
            if (null === $quote) {
                throw new ApiNotFoundException('Currency not found');
            }

            throw new ApiNotFoundException('Market not found');
        }
    }
}
