<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Api\Client;
use App\Entity\ApiKey;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Logger\UserActionLogger;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\OAuthServerBundle\Entity\ClientManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Rest\Route("/api/users")
 * @Security(expression="is_granted('prelaunch')")
 */
class UsersController extends AbstractFOSRestController
{
    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var ClientManager */
    private $clientManager;

    public function __construct(UserActionLogger $userActionLogger, ClientManager $clientManager)
    {
        $this->userActionLogger = $userActionLogger;
        $this->clientManager = $clientManager;
    }

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
        $this->userActionLogger->info('Created API keys');

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
        $this->userActionLogger->info('Deleted API keys');
    }

    /**
     * @Rest\View(statusCode=201)
     * @Rest\Post("/clients", name="post_client", options={"expose"=true})
     */
    public function createApiClient(): array
    {
        $user = $this->getUser();
        /** @var Client $client */
        $client = $this->clientManager->createClient();
        $client->setAllowedGrantTypes(['client_credentials']);
        $client->setUser($user);
        $this->clientManager->updateClient($client);

        return ['id' => $client->getPublicId(), 'secret' => $client->getSecret()];
    }

    /**
     * @Rest\View(statusCode=203)
     * @Rest\Delete("/clients", name="delete_client", options={"expose"=true})
     * @Rest\QueryParam(name="id", allowBlank=false, description="client id to delete")
     * @param ParamFetcherInterface $request
     * @return bool
     * @throws ApiNotFoundException
     */
    public function deleteApiClient(ParamFetcherInterface $request): bool
    {

        $id = $request->get('id');

        if (empty($id)) {
            throw new ApiNotFoundException("Client ID required");
        }

        $ids = explode('_', $id);

        if (count($ids) < 2) {
            throw new ApiNotFoundException("Wrong Client ID");
        }

        $user = $this->getUser();
        $client = $this->clientManager->findClientBy(['user' => $user, 'randomId' => $ids[1], 'id' => $ids[0]]);

        if (!($client instanceof Client)) {
            throw new ApiNotFoundException("No clients attached to the account");
        }

        $this->clientManager->deleteClient($client);
        $this->userActionLogger->info('Deleted API Client');

        return true;
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
