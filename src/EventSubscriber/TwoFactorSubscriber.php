<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Controller\TwoFactorAuthenticatedInterface;
use App\Entity\User;
use App\Manager\TwoFactorManagerInterface;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
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
            KernelEvents::CONTROLLER => 'onRequest',
        ];
    }

    /** {@inheritDoc} */
    public function onRequest(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller) || !$controller[0] instanceof TwoFactorAuthenticatedInterface) {
            return;
        }

        $request = $event->getRequest();
        $route = $this->router->getRouteCollection()->get(
            'en__RG__'.$request->attributes->get('_route')
        );

        if (!$route) {
            return;
        }

        $options = $route->getOptions();
        $option = $options['2fa'] ?? self::OFF;

        if (!in_array($option, self::VALUES)) {
            throw new InvalidArgumentException(
                "'2fa' option can only contain: " . (string)json_encode(self::VALUES)
            );
        }

        if (self::OFF === $option) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new UnauthorizedHttpException("2fa", "Invalid user");
        }

        /** @psalm-suppress UndefinedDocblockClass */
        $user = $token->getUser();

        if (!$user instanceof User) {
            throw new UnauthorizedHttpException("2fa", "Invalid user");
        }

        if (!$user->isGoogleAuthenticatorEnabled()) {
            if (self::OPTIONAL === $option) {
                return;
            }

            throw new UnauthorizedHttpException("2fa", "2FA is not enabled");
        }

        $code = $request->get('code');

        if (!$code || !$this->twoFactorManager->checkCode($user, $code)) {
            throw new UnauthorizedHttpException("2fa", "Invalid 2FA code");
        }
    }
}
