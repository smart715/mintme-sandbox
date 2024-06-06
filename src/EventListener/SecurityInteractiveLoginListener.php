<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Entity\User;
use App\Manager\AuthAttemptsManagerInterface;
use App\Manager\BlacklistIpManagerInterface;
use App\Manager\UserManagerInterface;
use App\Mercure\Authorization as MercureAuthorization;
use App\Services\TranslatorService\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SecurityInteractiveLoginListener
{
    private EntityManagerInterface $em;
    private AuthAttemptsManagerInterface $authAttemptsManager;
    private BlacklistIpManagerInterface $blacklistIpManager;
    private MercureAuthorization $mercureAuthorization;
    private UserManagerInterface $userManager;
    private TokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $em,
        AuthAttemptsManagerInterface $authAttemptsManager,
        BlacklistIpManagerInterface $blacklistIpManager,
        UserManagerInterface $userManager,
        MercureAuthorization $mercureAuthorization,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->authAttemptsManager = $authAttemptsManager;
        $this->userManager = $userManager;
        $this->blacklistIpManager = $blacklistIpManager;
        $this->mercureAuthorization = $mercureAuthorization;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();

        /*
         * security.interactive_login event should be fired on interactive login only (login form, not api or cookie)
         * but due to a bug in symfony with stateless api firewall, it's fired on every api request
         * therefore skipping /dev/api/v uri
         * they fixed it on 5.2, and refused to fix it on past versions
         * further info: https://github.com/symfony/symfony/issues/30733
         */
        if (false !== strpos($request->getRequestUri(), '/dev/api/v')) {
            return;
        }

        $session = $request->getSession();
        /** @var User $auth */
        $auth = $event->getAuthenticationToken()->getUser();

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $auth->getUsername()]);

        if ($user->isBlocked()) {
            /** @var Session $session */
            $session = $event->getRequest()->getSession();
            $session->invalidate();
            $this->tokenStorage->setToken(null);

            $session->getFlashBag()->set(
                'danger',
                $this->translator->trans('page.blocked.account', ['%email%' => $user->getEmail()])
            );

            return;
        }

        $sessionId = $session->getId();
        $this->userManager->saveSessionId($user, $sessionId);

        if ($user->getAuthAttempts()) {
            $this->authAttemptsManager->initChances($auth);
            $blacklistIp = $this->blacklistIpManager->getBlacklistIpByAddress($request->getClientIp());
            $this->blacklistIpManager->deleteBlacklistIp($blacklistIp);
        }

        $this->em->flush();

        $this->mercureAuthorization->setCookie($request, 'public');
    }
}
