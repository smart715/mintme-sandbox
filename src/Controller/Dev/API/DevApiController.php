<?php declare(strict_types = 1);

namespace App\Controller\Dev\API;

use App\Entity\Token\Token;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class DevApiController extends AbstractFOSRestController
{
    private const DISALLOWED_VALUES = [
        Token::WEB_SYMBOL,
    ];

    protected function checkForDisallowedValues(string $base, string $quote): void
    {
        if (in_array(mb_strtoupper($base), self::DISALLOWED_VALUES)
            || in_array(mb_strtoupper($quote), self::DISALLOWED_VALUES)) {
            throw new \Exception('Market not found', Response::HTTP_NOT_FOUND);
        }
    }
}
