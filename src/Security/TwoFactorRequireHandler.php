<?php declare(strict_types = 1);

namespace App\Security;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class TwoFactorRequireHandler implements AuthenticationRequiredHandlerInterface
{

    use TargetPathTrait;

    /** @var HttpUtils */
    private $httpUtils;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var RouterInterface */
    private $router;

    public function __construct(HttpUtils $httpUtils, TokenStorageInterface $tokenStorage, RouterInterface $router)
    {
        $this->httpUtils = $httpUtils;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    public function onAuthenticationRequired(Request $request, TokenInterface $token): Response
    {
        $routeName = $request->get('_route');
        $options = $this->router->getRouteCollection()->get($routeName)->getOptions();
        $is_2fa_progress = $options['2fa_progress'] ?? true;

        if (!$is_2fa_progress) {
            $request->getSession()->invalidate();
            $this->tokenStorage->setToken(null);
            $uri = $this->httpUtils->generateUri($request, $routeName);

            return $this->httpUtils->createRedirectResponse($request, $uri);
        }

        return $this->httpUtils->createRedirectResponse($request, TwoFactorFactory::DEFAULT_AUTH_FORM_PATH);
    }
}
