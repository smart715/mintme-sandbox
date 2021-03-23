<?php declare(strict_types = 1);

namespace App\Security\Request;

interface RefererRequestHandlerInterface
{
    public function getRefererPathData(): array;

    public function isRefererValid(string $pathInfo): bool;

    public function refererUrlsToSkip(): array;

    public function noRedirectToMainPage(string $referer): bool;

    public function refererRoutesForRedirectToMainPage(): array;
}
