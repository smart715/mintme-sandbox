<?php declare(strict_types = 1);

namespace App\Security\Firewall;

use App\Entity\User;
use App\Manager\AuthAttemptsManagerInterface;
use App\Manager\UserManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LoginFormAuthenticationListener extends UsernamePasswordFormAuthenticationListener
{
    private string $googleSecretKey;
    private UserManagerInterface $userManager;
    private FlashBagInterface $flashBag;
    private TranslatorInterface $translator;
    private AuthAttemptsManagerInterface $authAttemptsManager;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        HttpUtils $httpUtils,
        string $providerKey,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        CsrfTokenManagerInterface $csrfTokenManager,
        string $googleSecretKey,
        UserManagerInterface $userManager,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator,
        AuthAttemptsManagerInterface $authAttemptsManager
    ) {
        $this->googleSecretKey = $googleSecretKey;
        $this->userManager = $userManager;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->authAttemptsManager = $authAttemptsManager;

        parent::__construct(
            $tokenStorage,
            $authenticationManager,
            $sessionStrategy,
            $httpUtils,
            $providerKey,
            $successHandler,
            $failureHandler,
            $options,
            $logger,
            $dispatcher,
            $csrfTokenManager
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        /** @var User $user */
        $user = $this->userManager->findUserByEmail($request->request->get('_username'));

        if (null !== $user &&
            null !== $user->getAuthAttempts() &&
            !$this->authAttemptsManager->canDecrementChances($user)
        ) {
            $this->flashBag->set(
                'danger',
                $this->translator->trans(
                    'login.user_blocked',
                    [
                        '%hours%' => $this->authAttemptsManager->getMustWaitHours($user),
                    ]
                )
            );

            throw new AuthenticationException();
        }

        $recaptcha = new ReCaptcha($this->googleSecretKey);

        $reCaptchaToken = $request->get('g-recaptcha-response');
        $remoteIp = $request->getClientIp();

        $res = $recaptcha->verify($reCaptchaToken, $remoteIp);

        if (!$reCaptchaToken || !$res->isSuccess()) {
            throw new BadCredentialsException('captcha_login');
        }

        return parent::attemptAuthentication($request);
    }
}
