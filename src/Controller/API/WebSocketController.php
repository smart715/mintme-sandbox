<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Manager\ProfileManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Rest\Route("/api/ws")
 * @Security(expression="is_granted('prelaunch')")
 */
class WebSocketController extends AbstractFOSRestController
{
    /** @var bool */
    private $isAuth;

    public function __construct(bool $isAuth)
    {
        $this->isAuth = $isAuth;
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

            if (null == $token) {
                throw new RuntimeException('"Authorization" header was not found in HTTP request from via btc server', 1);
            }

            if (is_array($token)) {
                throw new RuntimeException(
                    'Array returned in "Authorization" header instead of an integer: '
                    . implode(', ', $token),
                    2
                );
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

            return $this->confirmed((int)$token);
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
