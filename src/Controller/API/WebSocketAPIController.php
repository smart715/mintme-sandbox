<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Crypto;
use App\Entity\User;
use App\Exchange\Config\Config;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\MarketNameParserInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Rest\Route("/api/ws")
 * @Security(expression="is_granted('prelaunch')")
 */
class WebSocketAPIController extends FOSRestController
{
    /** @var bool */
    private $isAuth;

    /** @var UserManagerInterface */
    private $userManager;

    /** @var Config */
    private $config;

    public function __construct(bool $isAuth, UserManagerInterface $userManager, Config $config)
    {
        $this->isAuth = $isAuth;
        $this->userManager = $userManager;
        $this->config = $config;
    }

    /**
     * @Rest\Get("/auth", name="auth")
     * @Rest\View()
     */
    public function authUser(Request $request, ProfileManagerInterface $profileManager): View
    {
        $token = $request->headers->get('authorization');

        if (null == $token || is_array($token)) {
            return $this->error();
        }

        $user = $this->isAuth ?
            $profileManager->findProfileByHash($token) :
            $this->userManager->find((int)$token);

        if (null === $user) {
            return $this->error();
        }

        $profileManager->createHash($user, false);

        return $this->confirmed($user);
    }

    private function error(): View
    {
        return $this->view([
            "error" => [
                "code" => 5,
                "message" => "service timeout",
            ],
            "result" => null,
            "id" => 0,
        ]);
    }

    private function confirmed(User $user): View
    {
        return $this->view([
            "code" => 0,
            "message" => null,
            "data" => ["user_id" => $user->getId() + $this->config->getOffset()],
        ]);
    }
}
