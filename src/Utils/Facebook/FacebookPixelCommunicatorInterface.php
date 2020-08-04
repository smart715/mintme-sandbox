<?php declare(strict_types = 1);

namespace App\Utils\Facebook;

use App\Entity\Profile;

interface FacebookPixelCommunicatorInterface
{
    public function __construct(string $appSecret, string $appID, string $pixelID, string $accessToken, bool $useTestCode, string $testCode);
    public function sendUserEvent(string $eventName, string $userEmail, string $userIP, string $userUserAgent, array $params, ?Profile $profile): void;
    public function sendEvent(string $eventName, string $userEmail, array $params, ?Profile $profile): void;
}
