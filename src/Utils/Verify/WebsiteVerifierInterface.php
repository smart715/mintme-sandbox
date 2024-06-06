<?php declare(strict_types = 1);

namespace App\Utils\Verify;

interface WebsiteVerifierInterface
{
    public const URIS = ['mintme.html', 'mintme.htm', 'mintme'];
    public const PREFIX = 'mintme-site-verification';

    public function verify(string $url, string $verificationToken): bool;
    public function verifyAirdropPostLinkAction(string $url, string $message): bool;
}
