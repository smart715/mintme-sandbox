<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Exchange\Config\Config;
use App\Manager\ProfileManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Rest\Route("/api/ws")
 */
class WebSocketController extends AbstractFOSRestController
{
    /** @var bool */
    private $isAuth;

    /** @var Config */
    private $config;

    public function __construct(bool $isAuth, Config $config)
    {
        $this->isAuth = $isAuth;
        $this->config = $config;
    }

    /**
     * @Rest\Get("/auth", name="auth")
     * @Rest\View()
     * @param Request $request
     * @param ProfileManagerInterface $profileManager
     * @return View
     */
    public function authUser(Request $request, ProfileManagerInterface $profileManager): View
    {
        try {
            $token = $request->headers->get('authorization');

            if (null === $token) {
                throw new RuntimeException('"Authorization" header was not found in HTTP request from via btc server', 1);
            }

            if (!$this->isAuth) {
                // return provided user id without verifying
                return $this->confirmed((int)$token);
            }

            // find user by hash
            $user = $profileManager->findProfileByHash($token);

            if (null === $user) {
                throw new RuntimeException('User with hash '.$token.' could not be found in mintme db', 3);
            }
            
            $profileManager->createHash($user, false);

            return $this->confirmed($user->getId() + $this->config->getOffset());
        } catch (RuntimeException $e) {
            return $this->view([
                "error" => [
                    "code" => $e->getCode(),
                    "message" => $e->getMessage(),
                ],
                "result" => null,
                "id" => null,
            ]);
        }
    }

    private function confirmed(int $userId): View
    {
        return $this->view([
            "code" => 0,
            "message" => null,
            "data" => ["user_id" => $userId],
        ]);
    }
}
