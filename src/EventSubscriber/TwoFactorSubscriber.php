<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Manager\TwoFactorManagerInterface;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TwoFactorSubscriber implements EventSubscriberInterface
{
    public const REQUIRED = 'required';
    public const OPTIONAL = 'optional';
    public const OFF = false;

    private const VALUES = [self::REQUIRED, self::OPTIONAL, self::OFF];

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TwoFactorManagerInterface */
    private $twoFactorManager;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TwoFactorManagerInterface $twoFactorManager,
        RouterInterface $router
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->twoFactorManager = $twoFactorManager;
        $this->router = $router;
    }

    /** @codeCoverageIgnore */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }

    /** {@inheritDoc} */
    public function onRequest(GetResponseEvent $request): void
    {
        $route = $this->router->getRouteCollection()->get(
            $request->getRequest()->attributes->get('_route')
        );

        if (!$route) {
            return;
        }

        $options = $route->getOptions();
        $option = $options['2fa'] ?? self::OFF;

        if (!in_array($option, self::VALUES)) {
            throw new InvalidArgumentException(
                "'2fa' option can only cointains: " . (string)json_encode(self::VALUES)
            );
        }

        if (self::OFF === $option) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new UnauthorizedHttpException("2fa", "Invalid user");
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            throw new UnauthorizedHttpException("2fa", "Invalid user");
        }

        if (!$user->isGoogleAuthenticatorEnabled()) {
            if (self::OPTIONAL === $option) {
                return;
            } else {
                throw new UnauthorizedHttpException("2fa", "2FA is not enabled");
            }
        }

        $code = $request->getRequest()->get('code');

        if (!$code || !$this->twoFactorManager->checkCode($user, $code)) {
            throw new UnauthorizedHttpException("2fa", "Invalid 2FA code");
        }
    }
}
