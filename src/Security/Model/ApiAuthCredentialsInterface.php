<?php declare(strict_types = 1);

namespace App\Security\Model;

interface ApiAuthCredentialsInterface
{
    public function getToken(): string;
}
