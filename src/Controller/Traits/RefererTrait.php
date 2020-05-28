<?php declare(strict_types = 1);

namespace App\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait RefererTrait
{
    public function isRefererValid(string $pathInfo): bool
    {
        $refererRequest = Request::create($pathInfo);
        $router = $this->get('router');
        $pathData = $router->match($refererRequest->getPathInfo());
        $routeName = $pathData['_route'] ?? null;

        return in_array($routeName, $this->validRefererRoutes());
    }

    private function refererUrlsToSkip(): array
    {
        return [
            $this->generateUrl('login', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->generateUrl('fos_user_registration_register', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }

    public function validRefererRoutes(): array
    {
        return [
            'token_show',
        ];
    }

    public function isRedirectToMainPage(string $referer): bool
    {
        return false === strpos($referer, '/token/')
            ? false
            : true;
    }
}
