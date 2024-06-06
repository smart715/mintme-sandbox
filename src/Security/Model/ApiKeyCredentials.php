<?php declare(strict_types = 1);

namespace App\Security\Model;

/** @codeCoverageIgnore */
class ApiKeyCredentials implements ApiAuthCredentialsInterface
{
    private ?string $private;
    private string $public;
    private bool $privateRequired;

    public function __construct(
        string $public,
        ?string $private,
        bool $privateRequired
    ) {
        $this->public = $public;
        $this->private = $private;
        $this->privateRequired = $privateRequired;
    }

    public function getPublic(): string
    {
        return $this->public;
    }

    public function getToken(): string
    {
        return $this->public;
    }

    public function getPrivate(): ?string
    {
        return $this->private;
    }

    public function isPrivateRequired(): bool
    {
        return $this->privateRequired;
    }
}
