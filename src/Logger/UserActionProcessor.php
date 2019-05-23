<?php declare(strict_types = 1);

namespace App\Logger;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Invokable class which adds remote IP address and username to the log entry.
 */
class UserActionProcessor
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
    }

    public function __invoke(array $record): array
    {
        $record['extra']['username'] = $this->getUsername();
        $record['extra']['ip_address'] = $this->requestStack->getCurrentRequest()->getClientIp();

        return $record;
    }

    private function getUsername(): string
    {
        $token = $this->tokenStorage->getToken();

        return $token
            ? $token->getUsername()
            : '';
    }
}
