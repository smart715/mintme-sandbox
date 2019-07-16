<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use Symfony\Component\HttpFoundation\Request;

interface DeployCostFetcherInterface
{
    /**
     * @throws FetchException
     * @return string
     */
    public function getDeployWebCost(): string;
}
