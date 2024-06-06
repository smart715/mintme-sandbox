<?php declare(strict_types = 1);

namespace App\Security\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RefererRequestHandler implements RefererRequestHandlerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    private array $refererPathData;

    public function getRefererPathData(): array
    {
        return $this->refererPathData;
    }

    public function isRefererValid(string $pathInfo): bool
    {
        $refererRequest = Request::create($pathInfo);
        $this->refererPathData = $this->router->match($refererRequest->getPathInfo());
        $routeName = $this->refererPathData['_route'] ?? null;

        return !in_array($routeName, $this->routesToSkip());
    }

    private function routesToSkip(): array
    {
        return [
            'fos_user_registration_register',
            'fos_user_resetting_reset',
            'login_success',
            'login',
            'nelmio_security',
            'airdrop_embeded',
            null,
        ];
    }

    public function refererUrlsToSkip(): array
    {
        return [
            $this->router->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->router->generate('fos_user_registration_register', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }
}
