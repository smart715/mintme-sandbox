<?php declare(strict_types = 1);

namespace App\Mercure;

use App\Entity\User;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Security\Core\Security;

class JwtProvider implements TokenProviderInterface
{
    private TokenFactoryInterface $factory;

    /** @var String[] */
    private array $subscribe;

    /** @var String[] */
    private array $publish;

    private Security $security;

    /**
     * @param String[] $subscribe
     * @param String[] $publish
     */
    public function __construct(
        TokenFactoryInterface $factory,
        array $subscribe,
        array $publish,
        Security $security
    ) {
        $this->factory = $factory;
        $this->subscribe = $subscribe;
        $this->publish = $publish;
        $this->security = $security;
    }

    public function getJwt(): string
    {
        $dynamicPublishTopics = [];

        /** @var User|null $user */
        $user = $this->security->getUser();

        if ($user) {
            $userId = $user->getId();

            $dynamicPublishTopics[] = 'withdraw/'.$userId;
        }

        $publish = array_merge($this->publish, $dynamicPublishTopics);

        return $this->factory->create($this->subscribe, $publish);
    }
}
