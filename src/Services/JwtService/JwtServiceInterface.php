<?php declare(strict_types = 1);

namespace App\Services\JwtService;

interface JwtServiceInterface
{
    public function createToken(array $payload, array $headers = []): string;
}
