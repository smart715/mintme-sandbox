<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Controller\TwoFactorAuthenticatedInterface;
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
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/users")
 */
class UsersController extends AbstractFOSRestController implements TwoFactorAuthenticatedInterface
{
    /** @var UserManagerInterface */
    protected $userManager;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var ClientManager */
    private $clientManager;

    /** @var TranslatorInterface */
    private $translations;

    public function __construct(
        UserManagerInterface $userManager,
        UserActionLogger $userActionLogger,
        ClientManager $clientManager,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translations
    ) {
        $this->userManager = $userManager;
        $this->userActionLogger = $userActionLogger;
        $this->clientManager = $clientManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->translations = $translations;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/keys", name="get_keys", options={"expose"=true})
     */
    public function getApiKeys(): ApiKey
    {
        $curUser = $this->getUser();
        $keys = null;

        if ($curUser instanceof User) {
            /** @var User $curUser */
            $keys = $curUser->getApiKey();
        }

        if (!$keys) {
            throw new ApiNotFoundException($this->translations->trans('api.user.no_keys_attached'));
        }

        return $keys;
    }

    /**
     * @Rest\View(statusCode=201)
     * @Rest\Post("/keys", name="post_keys", options={"expose"=true})
     * @return ApiKey|null
     */
    public function createApiKeys(): ?ApiKey
    {
        $user = $this->getUser();

        if (!$user) {
            throw new ApiBadRequestException($this->translations->trans('api.tokens.internal_error'));
        }

        /** @var User $user */
        if ($user->getApiKey()) {
            throw new ApiBadRequestException($this->translations->trans('api.user.key_already_created'));
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
        /** @var User $user*/
        $user = $this->getUser();

        $keys = $user->getApiKey();

        if (!$keys) {
            throw new ApiNotFoundException($this->translations->trans('api.user.no_keys_attached'));
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
        /** @var User|null $user*/
        $user = $this->getUser();

        if (!$user) {
            throw new ApiBadRequestException($this->translations->trans('api.tokens.internal_error'));
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
            throw new ApiNotFoundException($this->translations->trans('api.user.no_clients_attached'));
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
     * @Rest\RequestParam(name="currentPassword", nullable=false)
     * @Rest\RequestParam(name="plainPassword", nullable=false)
     * @Rest\RequestParam(name="code", nullable=true)
     * @throws ApiBadRequestException
     */
    public function changePassOnTwoFaActive(Request $request): Response
    {
        /** @var User|null $user*/
        $user = $this->getUser();

        if (!$user) {
            throw new ApiBadRequestException($this->translations->trans('api.tokens.internal_error'));
        }

        $errorOnPasswordForm = $this->checkStoredUserPassword($request, $user);

        if ($errorOnPasswordForm) {
            throw new ApiBadRequestException($errorOnPasswordForm);
        }

        $this->userManager->updatePassword($user);
        $this->userManager->updateUser($user);
        $response = new Response(Response::HTTP_OK);

        $event = new FilterUserResponseEvent($user, $request, $response);

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            $event
        );

        return $response;
    }

    /**
     * @Rest\View()
     * @Rest\Patch(
     *      "/settings/check-user-password",
     *      name="check-user-password",
     *      options={"expose"=true}
     * )
     * @Rest\RequestParam(name="currentPassword", nullable=false)
     * @Rest\RequestParam(name="plainPassword", nullable=false)
     * @throws ApiBadRequestException
     */
    public function checkUserPassword(Request $request): Response
    {
        /** @var User|null $user*/
        $user = $this->getUser();

        if (!$user) {
            throw new ApiBadRequestException($this->translations->trans('api.tokens.internal_error'));
        }

        $errorOnPasswordForm = $this->checkStoredUserPassword($request, $user);

        if ($errorOnPasswordForm) {
            throw new ApiBadRequestException($errorOnPasswordForm);
        }

        return new Response(Response::HTTP_OK);
    }

    private function checkStoredUserPassword(Request $request, User $user): ?string
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

            return $this->translations->trans('api.tokens.invalid_argument');
        }

        return null;
    }

    private function getEm(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * @return UserInterface|object|null
     */
    protected function getUser()
    {
        return parent::getUser();
    }
}
