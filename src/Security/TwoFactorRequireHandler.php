<?php declare(strict_types = 1);

namespace App\Security;

use App\Security\Request\RefererRequestHandlerInterface;
use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;

class TwoFactorRequireHandler implements AuthenticationRequiredHandlerInterface
{
    private HttpUtils $httpUtils;
    private TokenStorageInterface $tokenStorage;
    private RouterInterface $router;
    private UrlGeneratorInterface $urlGenerator;
    private RefererRequestHandlerInterface $refererRequestHandler;

    public function __construct(
        HttpUtils $httpUtils,
        TokenStorageInterface $tokenStorage,
        RouterInterface $router,
        UrlGeneratorInterface $urlGenerator,
        RefererRequestHandlerInterface $refererRequestHandler
    ) {
        $this->httpUtils = $httpUtils;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
        $this->refererRequestHandler = $refererRequestHandler;
    }

    public function onAuthenticationRequired(Request $request, TokenInterface $token): Response
    {
        $routeName = $request->get('_route');
        $is2faProgress = $this->router->match($request->getPathInfo())['2fa_progress'] ?? true;
        $uri = $this->httpUtils->generateUri($request, $routeName);

        if (!$is2faProgress || $this->refererRequestHandler->isRefererValid($uri)) {
            $request->getSession()->invalidate();
            $this->tokenStorage->setToken(null);

            return $this->httpUtils->createRedirectResponse($request, $uri);
        }

        return $this->httpUtils->createRedirectResponse($request, $this->urlGenerator->generate('2fa_login'));
    }
}
