<?php declare(strict_types = 1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\RedirectResponse;

/** @codeCoverageIgnore  */
class RedirectException extends \Exception
{
    private RedirectResponse $response;

    public function __construct(RedirectResponse $response)
    {
        $this->response = $response;
        parent::__construct();
    }

    public function getResponse(): RedirectResponse
    {
        return $this->response;
    }
}
