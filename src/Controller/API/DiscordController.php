<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\DiscordOAuthClientInterface;
use App\Entity\DiscordRole;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Exception\Discord\DiscordException;
use App\Exception\Discord\MissingPermissionsException;
use App\Form\DiscordRoleType;
use App\Manager\DiscordConfigManager;
use App\Manager\DiscordManagerInterface;
use App\Manager\DiscordRoleManagerInterface;
use App\Manager\TokenManagerInterface;
use Discord\InteractionResponseType;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use RestCord\DiscordClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/discord")
 */
class DiscordController extends AbstractFOSRestController
{
    private TokenManagerInterface $tokenManager;
    private DiscordManagerInterface $discordManager;
    private DiscordRoleManagerInterface $discordRoleManager;
    private DiscordConfigManager $discordConfigManager;
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;
    private DiscordOAuthClientInterface $discordOAuthClient;

    public function __construct(
        TokenManagerInterface $tokenManager,
        DiscordManagerInterface $discordManager,
        DiscordRoleManagerInterface $discordRoleManager,
        DiscordConfigManager $discordConfigManager,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        DiscordOAuthClientInterface $discordOAuthClient
    ) {
        $this->tokenManager = $tokenManager;
        $this->discordManager = $discordManager;
        $this->discordRoleManager = $discordRoleManager;
        $this->discordConfigManager = $discordConfigManager;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->discordOAuthClient = $discordOAuthClient;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{tokenName}/info", name="get_discord_info", options={"expose"=true})
     */
    public function getRoles(string $tokenName): View
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $token);

        return $this->view([
            'config' => $token->getDiscordConfig(),
            'roles' => $token->getDiscordRoles(),
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/roles", name="manage_discord_roles", options={"expose"=true})
     * @Rest\RequestParam(name="newRoles", allowBlank=true, nullable=true)
     * @Rest\RequestParam(name="currentRoles", allowBlank=true, nullable=true)
     * @Rest\RequestParam(name="removedRoles", allowBlank=true, nullable=true)
     * @Rest\RequestParam(name="specialRolesEnabled", nullable=false)
     * @throws ApiBadRequestException
     */
    public function manageRoles(string $tokenName, ParamFetcherInterface $request): View
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (!$token->getDiscordConfig()->hasGuild()) {
            throw new ApiBadRequestException();
        }

        $removedRolesData = $request->get('removedRoles');
        $removedRoles = $this->deleteRoles($token, $removedRolesData);

        $this->entityManager->flush();

        $currentRolesData = $request->get('currentRoles');
        $editedRoles = $this->editRoles($token, $currentRolesData);

        $newRolesData = $request->get('newRoles');
        $newRoles = $this->addRoles($token, $newRolesData);

        $rolesCount = count($token->getDiscordRoles());
        $specialRolesEnabled = $rolesCount && $request->get('specialRolesEnabled');
        $token->getDiscordConfig()->setSpecialRolesEnabled($specialRolesEnabled);

        try {
            $this->manageRolesOnDiscord($newRoles, $editedRoles, $removedRoles);
        } catch (MissingPermissionsException $e) {
            return $this->view([
                'errors' => true,
                'enabled' => false,
                'message' => $this->translator->trans('discord.error.missing_permissions'),
            ], Response::HTTP_OK);
        } catch (DiscordException $e) {
            return $this->view([
                'errors' => true,
                'enabled' => true,
                'message' => $this->translator->trans('discord.error'),
            ], Response::HTTP_OK);
        }

        foreach ($newRoles as $role) {
            $token->addDiscordRole($role);
        }

        $this->entityManager->persist($token);

        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            return $this->view(
                ['message' => $this->translator->trans('api.something_went_wrong')],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->view(['status' => 'success'], Response::HTTP_OK);
    }

    /**
     * @return DiscordRole[]
     */
    private function addRoles(Token $token, array $rolesData): array
    {
        $roles = [];

        foreach ($rolesData as $roleData) {
            $role = (new DiscordRole())->setToken($token);

            $form = $this->createForm(DiscordRoleType::class, $role);
            $form->submit($roleData);

            if (!$form->isValid()) {
                throw new ApiBadRequestException($this->translator->trans('discord.error.invalid_form'));
            }

            $roles[] = $role;
        }

        return $roles;
    }

    /**
     * @return DiscordRole[]
     */
    private function editRoles(Token $token, array $rolesData): array
    {
        $roles = [];

        foreach ($rolesData as $roleData) {
            $criteria = Criteria::create();
            $criteria->where(
                Criteria::expr()->eq('id', $roleData['id'])
            )->getFirstResult();

            /** @var DiscordRole $role */
            $role = $token->getDiscordRolesMatching($criteria)->first();

            $form = $this->createForm(DiscordRoleType::class, $role);
            $form->submit($roleData);

            if (!$form->isValid()) {
                throw new ApiBadRequestException($this->translator->trans('discord.error.invalid_form'));
            }

            if ($role->hasChanged()) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    /**
     * @return DiscordRole[]
     */
    private function deleteRoles(Token $token, array $rolesData): array
    {
        $roles = [];

        foreach ($rolesData as $roleData) {
            $criteria = Criteria::create();
            $criteria->where(
                Criteria::expr()->eq('id', $roleData['id'])
            )->getFirstResult();

            /** @var DiscordRole $role */
            $role = $token->getDiscordRolesMatching($criteria)->first();

            $token->removeDiscordRole($role);
            $this->entityManager->remove($role);

            $roles[] = $role;
        }

        return $roles;
    }

    /**
     * @param DiscordRole[] $newRoles
     * @param DiscordRole[] $editedRoles
     * @param DiscordRole[] $deletedRoles
     * @throws DiscordException
     * @throws MissingPermissionsException
     */
    private function manageRolesOnDiscord(array $newRoles, array $editedRoles, array $deletedRoles): void
    {
        $this->discordManager->createRoles($newRoles);
        $this->discordManager->updateRoles($editedRoles);
        $this->discordManager->deleteRoles($deletedRoles);
    }

    /**
     * @Rest\Get("/callback/user", name="discord_callback_user")
     * @Rest\QueryParam(name="code")
     */
    public function userCallback(ParamFetcherInterface $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $code = $request->get('code');

        if (!$code) {
            return $this->redirectToRoute('settings');
        }

        $redirectUrl = $this->generateUrl('discord_callback_user', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $accessToken = $this->discordOAuthClient->getAccessToken($code, $redirectUrl);

        $discord = new DiscordClient(['token' => $accessToken, 'tokenType' => 'OAuth']);

        $discordUser = $discord->user->getCurrentUser([]);

        $user->setDiscordId($discordUser->id);

        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            if (preg_match('/discordid/i', $e->getMessage())) {
                $this->addFlash('danger', $this->translator->trans('discord.error.account_already_used'));
            }
        }

        return $this->redirectToRoute('settings');
    }

    /**
     * @Rest\Get("/callback/bot", name="discord_callback_bot")
     * @Rest\QueryParam(name="guild_id")
     * @Rest\QueryParam(name="state")
     * @Rest\QueryParam(name="permissions")
     */
    public function botCallback(ParamFetcherInterface $request): RedirectResponse
    {
        $guildId = (int)$request->get('guild_id');
        $permissions = (int)$request->get('permissions');
        $tokenId = (int)$request->get('state');

        $token = $this->tokenManager->findById($tokenId);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if ($guildId && DiscordOAuthClientInterface::BOT_PERMISSIONS_ADMINISTRATOR === $permissions) {
            $config = $token->getDiscordConfig();

            if ($config->hasGuild() && $guildId !== $config->getGuildId()) {
                $this->discordRoleManager->removeRoles($token);
            }

            $config->setGuildId($guildId)->setEnabled(true);

            $this->entityManager->persist($config);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('token_show', ['name' => $token->getName()]);
    }

    /**
     * @Rest\View
     * @Rest\Post("/interaction", name="discord_interaction")
     */
    public function interaction(Request $request): View
    {
        $body = $request->getContent();

        $headers = $request->headers;

        $signature = $headers->get('HTTP-X-SIGNATURE-ED25519');
        $timestamp = $headers->get('HTTP-X-SIGNATURE-TIMESTAMP');

        $isInteractionValid = $this->discordManager->verifyInteraction($body, $signature, $timestamp);

        if (!$isInteractionValid) {
            return $this->view(['message' => 'invalid request signature', Response::HTTP_UNAUTHORIZED]);
        }

        $params = \json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (InteractionResponseType::PONG === $params['type']) {
            return $this->view(['type' => InteractionResponseType::PONG], Response::HTTP_OK);
        }

        return $this->view([]); // @TODO
    }

    /**
     * @Rest\View()
     * @Rest\Delete("/{tokenName}/guild", name="remove_guild", options={"expose"=true})
     */
    public function removeGuild(string $tokenName): View
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $token);

        $this->discordManager->leaveGuild($token);

        $config = $token->getDiscordConfig()->setGuildId(null);

        $this->discordConfigManager->disable($config);

        $this->discordRoleManager->removeRoles($token);

        return $this->view(['status' => 'success'], Response::HTTP_OK);
    }
}
