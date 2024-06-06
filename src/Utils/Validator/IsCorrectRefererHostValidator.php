<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use Symfony\Component\HttpFoundation\Request;

class IsCorrectRefererHostValidator implements ValidatorInterface
{
    public const DISCORD_HOST = 'discord.com';

    private string $message = 'Incorrect referer host'; // phpcs:ignore

    private Request $request;
    private string $host;

    public function __construct(Request $request, string $host)
    {
        $this->request = $request;
        $this->host = $host;
    }

    public function validate(): bool
    {
        /** @var string|array|null $referers */
        $referers = $this->request->headers->get('referer');

        if (!$referers) {
            return false;
        }

        $referer = is_array($referers)
            ? (string)$referers[0]
            : $referers;

        $parsedUrl = parse_url($referer);

        if (!is_array($parsedUrl) || !array_key_exists('host', $parsedUrl)) {
            return false;
        }

        $truncatedHost = str_replace('www.', '', $parsedUrl['host']);
        
        return $truncatedHost === $this->host;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
