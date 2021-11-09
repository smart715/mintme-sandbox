<?php declare(strict_types = 1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessMapInterface;

class PathRoles
{
    /** @var AccessMapInterface $accessMap */
    protected $accessMap;

    public function __construct(AccessMapInterface $accessMap)
    {
        $this->accessMap = $accessMap;
    }

    public function getRoles(Request $request): ?array
    {
        $patterns = $this->accessMap->getPatterns($request);

        return !empty($patterns)
            ? $patterns[0]
            : null;
    }
}
