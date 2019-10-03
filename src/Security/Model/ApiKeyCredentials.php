<?php declare(strict_types = 1);

namespace App\Security\Model;

class ApiKeyCredentials
{
    /** @var string */
    private $private;

    /** @var string */
    private $public;

    public function __construct(string $public, string $private)
    {
        $this->public = $public;
        $this->private = $private;
    }

    public function getPublic(): string
    {
        return $this->public;
    }

    public function getPrivate(): string
    {
        return $this->private;
    }
}
