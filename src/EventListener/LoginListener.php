<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Config\FailedLoginConfig;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\AuthAttemptsManagerInterface;
use App\Manager\BlacklistIpManagerInterface;
use App\Manager\UserManager;
use App\Services\TranslatorService\TranslatorInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

class LoginListener
{
    private FlashBagInterface $flashBag;
    private UserManager $userManager;
    private AuthAttemptsManagerInterface $authAttemptsManager;
    private TranslatorInterface $translator;
    private FailedLoginConfig $failedLoginConfig;
    private MailerInterface $mailer;
    private BlacklistIpManagerInterface $blacklistIpManager;
    private RequestStack $requestStack;

    public function __construct(
        UserManager $userManager,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator,
        AuthAttemptsManagerInterface $authAttemptsManager,
        FailedLoginConfig $failedLoginConfig,
        MailerInterface $mailer,
        BlacklistIpManagerInterface $blacklistIpManager,
        RequestStack $requestStack
    ) {
        $this->userManager = $userManager;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->authAttemptsManager = $authAttemptsManager;
        $this->failedLoginConfig = $failedLoginConfig;
        $this->mailer = $mailer;
        $this->blacklistIpManager = $blacklistIpManager;
        $this->requestStack = $requestStack;
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        if ($event->getAuthenticationToken() instanceof TwofactorToken) {
            return;
        }

        $address = $this->requestStack->getCurrentRequest()->getClientIp();
        $blacklistIp = $this->blacklistIpManager
            ->getBlacklistIpByAddress($address);
        $this->blacklistIpManager->decrementChances($blacklistIp, $address);

        if ($this->blacklistIpManager->isBlacklistedIp($blacklistIp)) {
            return;
        }

        $userName = $event->getAuthenticationToken()->getUsername();

        /** @var User|null $user */
        $user = $this->userManager->findUserByEmail($userName);

        if (null === $user) {
            return;
        }

        if ($user->isBlocked()) {
            return;
        }

        if ($this->authAttemptsManager->canDecrementChances($user)) {
            $chances = $this->authAttemptsManager->decrementChances($user);

            if (0 === $chances) {
                $this->flashBlocked();
                $this->mailer->sendFailedLoginBlock($user);

                return;
            }

            $this->flashBag->set(
                'danger',
                $this->translator->trans(
                    'login.chance_failure',
                    ['%chances%' => $chances]
                )
            );

            return;
        }

        $this->flashBlocked();
    }

    public function flashBlocked(): void
    {
        $this->flashBag->set(
            'danger',
            $this->translator->trans(
                'login.user_blocked',
                ['%hours%' => $this->failedLoginConfig->getMaxHours()]
            )
        );
    }
}
