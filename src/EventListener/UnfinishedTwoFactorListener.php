<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Entity\User;
use App\Logger\UserActionLogger;
use App\Manager\TwoFactorManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UnfinishedTwoFactorListener
{
    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var TwoFactorManagerInterface */
    private $twoFactorManager;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var SessionInterface*/
    private $session;

    /** @var tokenStorageInterface */
    private $tokenStorage;

    public function __construct(
        UserActionLogger $userActionLogger,
        TwoFactorManagerInterface $twoFactorManager,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        TokenStorageInterface $tokenStorage
    ) {
        $this->userActionLogger = $userActionLogger;
        $this->twoFactorManager = $twoFactorManager;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelRequest(GetResponseEvent $request): void
    {
        $route = $request->getRequest()->attributes->get('_route');

        /** @var AttributeBagInterface */
        $attributeBag = $this->session->getBag('attributes');
        $isTwoFactorFinished = $attributeBag->has('2fa_finished');

        if ($isTwoFactorFinished && 'two_factor_finished' !== $route) {
            $token = $this->tokenStorage->getToken();

            if (!$token) {
                return;
            }

            $user = $token->getUser();

            if (!($user instanceof User)) {
                return;
            }

            $googleAuth = $this->twoFactorManager->getGoogleAuthEntry($user->getId());

            $this->entityManager->remove($googleAuth);
            $this->entityManager->flush();

            $this->userActionLogger->info('Two-Factor Authentication was disabled because it was not finished');

            $attributeBag->remove('2fa_finished');
        }
    }
}
