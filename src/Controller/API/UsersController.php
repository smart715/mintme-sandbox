<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Logger\UserActionLogger;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\OAuthServerBundle\Entity\ClientManager;
use FOS\RestBundle\Request\ParamFetcherInterface;

/**
 * @Rest\Route("/api/users")
 * @Security(expression="is_granted('prelaunch')")
 */
class UsersController extends AbstractFOSRestController
{
    /** @var UserActionLogger */
    private $userActionLogger;
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
        /* App\Entity\Api\Client $client */
        $user = $this->getUser();
        $client = $this->clientManager->createClient();
        $client->setAllowedGrantTypes(array('token'));
        $client->setUser($user);
        $this->clientManager->updateClient($client);

        $clients = $user->GetApiClients();

        // add secret only to new created client
        // for other it will be hidden
        foreach ($clients as $key=>$val){
            if ($val['id'] ==  $client->getRandomId()){
                $clients[$key]['secret'] =  $client->getSecret();
            }
        }

        return $clients;
    }

    /**
     * @Rest\View(statusCode=203)
     * @Rest\Delete("/clients", name="delete_client", options={"expose"=true})
     * @Rest\QueryParam(name="id", allowBlank=false, description="client id to delete")
     * @param ParamFetcherInterface $request
     * @return array []Client
     * @throws ApiNotFoundException
     */
    public function deleteApiClient(ParamFetcherInterface $request): array
    {

        $id = (string)$request->get('id');
        if (empty($id)) {
            throw new ApiNotFoundException("Client ID required");
        }

        $user = $this->getUser();
        $client = $this->clientManager->findClientBy(['user' => $user, 'randomId' => $id]);

        if (!($client instanceof \App\Entity\Api\Client)) {
            throw new ApiNotFoundException("No clients attached to the account");
        }

        $this->clientManager->deleteClient($client);
        $this->userActionLogger->info('Deleted API Client');

        $clients = $user->GetApiClients();

        return $clients;
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
