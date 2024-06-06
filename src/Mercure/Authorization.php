<?php declare(strict_types = 1);

namespace App\Mercure;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Authorization as MercureAuthorization;
use Symfony\Component\Security\Core\Security;

class Authorization
{
    private MercureAuthorization $mercureAuthorization;

    /**
     * @param String[] $subscribe
     */
    private array $subscribe;

    /**
     * @param String[] $publish
     */
    private array $publish;

    private Security $security;

    public function __construct(
        MercureAuthorization $mercureAuthorization,
        array $subscribe,
        array $publish,
        Security $security
    ) {
        $this->mercureAuthorization = $mercureAuthorization;
        $this->subscribe = $subscribe;
        $this->publish = $publish;
        $this->security = $security;
    }

    public function setCookie(Request $request, ?string $hub = null): void
    {
        $dynamicSubscribeTopics = [];

        /** @var User|null $user */
        $user = $this->security->getUser();

        if ($user) {
            $userId = $user->getId();

            $dynamicSubscribeTopics[] = 'withdraw/'.$userId;
        }

        $subscribe = array_merge($this->subscribe, $dynamicSubscribeTopics);
        $this->mercureAuthorization->setCookie(
            $request,
            $subscribe,
            $this->publish,
            [ 'exp' => new \DateTimeImmutable('+1 hour') ],
            $hub
        );
    }

    public function clearCookie(Request $request, ?string $hub = null): void
    {
        $this->mercureAuthorization->clearCookie($request, $hub);
    }
}
