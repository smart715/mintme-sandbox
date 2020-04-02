<?php declare(strict_types = 1);

namespace App\Logger;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @codeCoverageIgnore
 */
class FrontEndProcessor extends UserActionProcessor
{
    /** @var RequestStack */
    private $requestStack;


    public function __construct(
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;

        parent::__construct($tokenStorage, $requestStack);
    }

    public function __invoke(array $record): array
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        $record['extra']['username'] = $this->getUsername();
        $record['extra']['ip_address'] = $request->getClientIp();
        $record['extra']['url'] = $request->headers->get('referer');

        return $record;
    }
}
