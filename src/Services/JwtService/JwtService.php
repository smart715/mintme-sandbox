<?php declare(strict_types = 1);

namespace App\Services\JwtService;

use SimpleJWT\JWT;
use SimpleJWT\Keys\KeySet;
use SimpleJWT\Keys\RSAKey;

class JwtService implements JwtServiceInterface
{
    private const ALGORITHM_RS256 = 'RS256';
    private const TYPE_JWT = 'JWT';

    private RSAKey $rsaKey;

    public function __construct(string $pemFile, ?string $passPhrase)
    {
        $this->setKeyFile($pemFile, $passPhrase);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     */
    public function createToken(
        array $payload,
        array $headers = []
    ): string {
        $keySet = new KeySet();
        $keySet->add($this->rsaKey);

        $headers = array_merge([
            'alg' => self::ALGORITHM_RS256,
            'typ' => self::TYPE_JWT,
        ], $headers);

        $claims = array_merge([
            'exp' => time() + 3600,
        ], $payload);

        return (new JWT($headers, $claims))->encode($keySet);
    }

    private function setKeyFile(string $pemFile, ?string $passPhrase): void
    {
        if (!file_exists($pemFile)) {
            throw new \InvalidArgumentException('File not found');
        }
        
        $this->rsaKey = new RSAKey((string)file_get_contents($pemFile), 'pem', $passPhrase);
    }
}
