<?php declare(strict_types = 1);

namespace App\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait RefererTrait
{
    private array $refererPathData;

    public function getRefererPathData(): array
    {
        return $this->refererPathData;
    }

    public function isRefererValid(string $pathInfo): bool
    {
        $refererRequest = Request::create($pathInfo);
        $router = $this->get('router');
        $this->refererPathData = $router->match($refererRequest->getPathInfo());
        $routeName = $this->refererPathData['_route'] ?? null;

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
            'coin',
            'token_show',
            'show_post',
        ];
    }

    public function noRedirectToMainPage(string $referer): bool
    {
        $noRedirect = true;

        foreach ($this->refererRoutesForRedirectToMainPage() as $refererRoute) {
            if (false !== strpos($referer, $refererRoute)) {
                $noRedirect = false;

                break;
            }
        }

        return $noRedirect;
    }

    public function refererRoutesForRedirectToMainPage(): array
    {
        return [
            '/token/',
            '/profile/',
        ];
    }
}
