<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Rest\Route("/api/users")
 * @Security(expression="is_granted('prelaunch')")
 */
class UsersController extends AbstractFOSRestController
{
    /**
     * @Rest\View()
     * @Rest\Get("/keys", name="get_keys", options={"expose"=true})
     */
    public function getApiKeys(): ApiKey
    {
        $keys = $this->getUser()->getApiKey();

        if (!$keys) {
            throw new ApiNotFoundException("No keys attached to the account");
        }

        return $keys;
    }

    /**
     * @Rest\View(statusCode=201)
     * @Rest\Post("/keys", name="post_keys", options={"expose"=true})
     */
    public function createApiKeys(): ApiKey
    {
        if ($this->getUser()->getApiKey()) {
            throw new ApiBadRequestException("Keys already created");
        }

        $keys = ApiKey::fromNewUser($this->getUser());
        $this->getEm()->persist($keys);
        $this->getEm()->flush();

        return $keys;
    }

    /**
     * @Rest\View(statusCode=203)
     * @Rest\Delete("/keys", name="delete_keys", options={"expose"=true})
     */
    public function invalidateApiKeys(): void
    {
        $user = $this->getUser();
        $keys = $user->getApiKey();

        if (!$keys) {
            throw new ApiNotFoundException("No keys attached to the account");
        }

        $this->getEm()->remove($keys);
        $this->getEm()->flush();
    }

    private function getEm(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }

    protected function getUser(): User
    {
        return parent::getUser();
    }
}
