<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Logger\UserActionLogger;
use App\Services\TranslatorService\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class BruteForceListener
{
    private const MAX_ATTEMPT_COUNT = 10;
    private const ATTEMPTS_KEY = 'attempts';

    /** @var UserActionLogger */
    private $logger;

    /** @var Session */
    private $session;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var string */
    private $secret;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        UserActionLogger $logger,
        Session $session,
        TokenStorageInterface $tokenStorage,
        string $secret,
        TranslatorInterface $translator
    ) {
        $this->logger = $logger;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->secret = $secret;
        $this->translator = $translator;
    }

    public function onSchebtwofactorAuthenticationAttempt(): void
    {
        return;
    }

    public function onSchebtwofactorAuthenticationSuccess(): void
    {
        if (0 !== $this->getAttempts()) {
            $this->session->remove(self::ATTEMPTS_KEY);
        }
    }

    public function onSchebtwofactorAuthenticationFailure(): void
    {
        $attempts = $this->getAttempts();

        $this->session->set(self::ATTEMPTS_KEY, ++$attempts);

        $this->logger->warning('Failed to authenticate.', [
            'attempts' => $attempts,
        ]);

        if ($this->getAttempts() >= self::MAX_ATTEMPT_COUNT) {
            $this->tokenStorage->setToken(new AnonymousToken($this->secret, 'anon.', []));
            $this->session->invalidate();
            $this->session->getFlashBag()->set(
                'danger',
                $this->translator->trans('2fa.limit_exceeded')
            );

            throw new AuthenticationException();
        }

        $this->session->getFlashBag()->set(
            'danger',
            $this->translator->trans('2fa.chance_failure')
        );
    }

    private function getAttempts(): int
    {
        return $this->session->get(self::ATTEMPTS_KEY) ?? 0;
    }
}
