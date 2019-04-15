<?php declare(strict_types = 1);

namespace App\Utils\Verify;

interface WebsiteVerifierInterface
{
    public const URI = 'mintme.html';
    public const PREFIX = 'mintme-site-verification';

    public function verify(string $url, string $verificationToken): bool;
}
