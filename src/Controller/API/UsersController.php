<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Api\Client;
use App\Entity\ApiKey;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Form\ChangePasswordType;
use App\Logger\UserActionLogger;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\OAuthServerBundle\Entity\ClientManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/users")
 */
class UsersController extends AbstractFOSRestController
{
    /** @var UserManagerInterface */
    protected $userManager;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var ClientManager */
    private $clientManager;

    public function __construct(
        UserManagerInterface $userManager,
        UserActionLogger $userActionLogger,
        ClientManager $clientManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userManager = $userManager;
        $this->userActionLogger = $userActionLogger;
        $this->clientManager = $clientManager;
        $this->eventDispatcher = $eventDispatcher;
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
        $user = $this->getUser();

        if (!$user) {
            throw new ApiBadRequestException('Internal error, Please try again later');
        }

        if ($user->getApiKey()) {
            throw new ApiBadRequestException("Keys already created");
        }

        $keys = ApiKey::fromNewUser($user);

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

        if (!$user) {
            throw new ApiBadRequestException('Internal error, Please try again later');
        }

        /** @var Client $client */
        $client = $this->clientManager->createClient();
        $client->setAllowedGrantTypes(['client_credentials']);
        $client->setUser($user);
        $this->clientManager->updateClient($client);

        return ['id' => $client->getPublicId(), 'secret' => $client->getSecret()];
    }

    /**
     * @Rest\View(statusCode=203)
     * @Rest\Delete(
     *     "/clients/{id}",
     *     name="delete_client",
     *     requirements={"id"="^\d+_[a-zA-Z0-9]+$"},
     *     options={"expose"=true}
     * )
     * @param string $id
     * @return bool
     * @throws ApiNotFoundException
     */
    public function deleteApiClient(string $id): bool
    {
        $ids = explode('_', $id);

        $user = $this->getUser();
        $client = $this->clientManager->findClientBy(['user' => $user, 'randomId' => $ids[1], 'id' => $ids[0]]);

        if (!($client instanceof Client)) {
            throw new ApiNotFoundException("No clients attached to the account");
        }

        $this->clientManager->deleteClient($client);
        $this->userActionLogger->info('Deleted API Client');

        return true;
    }

    /**
     * @Rest\View()
     * @Rest\Patch(
     *      "/settings/update-password",
     *      name="update-password",
     *      options={"2fa"="optional", "expose"=true}
     * )
     * @Rest\RequestParam(name="current_password", nullable=false)
     * @Rest\RequestParam(name="plainPassword", nullable=false)
     * @Rest\RequestParam(name="code", nullable=true)
     * @throws ApiBadRequestException
     */
    public function changePassOnTwoFaActive(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw new ApiBadRequestException('Internal error, Please try again later');
        }

        $errorOnPasswordForm = $this->matchPasswords($request, $user);

        if ($errorOnPasswordForm) {
            throw new ApiBadRequestException($errorOnPasswordForm);
        }

        $this->userManager->updatePassword($user);
        $this->userManager->updateUser($user);
        $response = new Response(Response::HTTP_ACCEPTED);

        $event = new FilterUserResponseEvent($user, $request, $response);
        $this->eventDispatcher->dispatch(
            FOSUserEvents::CHANGE_PASSWORD_COMPLETED,
            $event
        );

        return $response;
    }

    /**
     * @Rest\View()
     * @Rest\Patch(
     *      "/settings/match-password",
     *      name="match-password",
     *      options={"expose"=true}
     * )
     * @Rest\RequestParam(name="current_password", nullable=false)
     * @Rest\RequestParam(name="plainPassword", nullable=false)
     * @throws ApiBadRequestException
     */
    public function checkMatchPasswords(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw new ApiBadRequestException('Internal error, Please try again later');
        }

        $errorOnPasswordForm = $this->matchPasswords($request, $user);

        if ($errorOnPasswordForm) {
            throw new ApiBadRequestException($errorOnPasswordForm);
        }

        return new Response(Response::HTTP_ACCEPTED);
    }

    private function matchPasswords(Request $request, User $user): ?string
    {
        $changePasswordData = $request->request->all();
        $passwordForm = $this->createForm(ChangePasswordType::class, $user, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);

        $passwordForm->submit(array_filter($changePasswordData, function ($value) {
            return null !== $value;
        }), false);

        if (!$passwordForm->isValid()) {
            foreach ($passwordForm->all() as $childForm) {
                /** @var FormError[] $fieldErrors */
                $fieldErrors = $passwordForm->get($childForm->getName())->getErrors();

                if (count($fieldErrors) > 0) {
                    return $fieldErrors[0]->getMessage();
                }
            }

            return 'Invalid Argument';
        }

        return null;
    }

    private function getEm(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }

    protected function getUser(): ?User
    {
        return parent::getUser();
    }
}
