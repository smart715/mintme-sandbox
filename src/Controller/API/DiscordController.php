<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\DiscordOAuthClientInterface;
use App\Entity\DiscordRole;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Form\DiscordRoleType;
use App\Manager\DiscordConfigManager;
use App\Manager\DiscordManagerInterface;
use App\Manager\DiscordRoleManagerInterface;
use App\Manager\TokenManagerInterface;
use Discord\InteractionResponseType;
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
            'roles' => $token->getDiscordRoles()->toArray(),
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

        $rolesFromDiscord = $this->discordManager->getManageableRoles($this->discordManager->getGuild($token));

        $removedRolesData = $request->get('removedRoles');
        $this->deleteRoles($token, $removedRolesData);

        $this->entityManager->flush();

        $currentRolesData = $request->get('currentRoles');
        $this->editRoles($token, $currentRolesData);

        $newRolesData = $request->get('newRoles');
        $this->addRoles($token, $newRolesData, $rolesFromDiscord);

        $rolesCount = $token->getDiscordRoles()->count();
        $specialRolesEnabled = $rolesCount && $request->get('specialRolesEnabled');
        $token->getDiscordConfig()->setSpecialRolesEnabled($specialRolesEnabled);

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

    private function addRoles(Token $token, array $rolesData, array $rolesFromDiscord): void
    {
        foreach ($rolesData as $roleData) {
            $role = $rolesFromDiscord[$roleData['discordId']] ?? null;

            if (!$role) {
                continue;
            }

            $role->setToken($token);

            $form = $this->createForm(DiscordRoleType::class, $role);
            $form->submit($roleData);

            if (!$form->isValid()) {
                throw new ApiBadRequestException($this->translator->trans('discord.error.invalid_form'));
            }

            $token->addDiscordRole($role);
            $this->entityManager->persist($role);
        }
    }

    private function editRoles(Token $token, array $rolesData): void
    {
        $currentRoles = $token->getDiscordRoles();

        foreach ($rolesData as $roleData) {
            /** @var DiscordRole|null $role */
            $role = $currentRoles->filter(
                fn (DiscordRole $r) => $r->getDiscordId() === (int)$roleData['discordId']
            )->first();

            if (!$role) {
                continue;
            }

            $form = $this->createForm(DiscordRoleType::class, $role);
            $form->submit($roleData);

            if (!$form->isValid()) {
                throw new ApiBadRequestException($this->translator->trans('discord.error.invalid_form'));
            }

            $this->entityManager->persist($role);
        }
    }

    private function deleteRoles(Token $token, array $rolesData): void
    {
        $currentRoles = $token->getDiscordRoles();

        foreach ($rolesData as $roleData) {
            /** @var DiscordRole|null $role */
            $role = $currentRoles->filter(
                fn (DiscordRole $r) => $r->getDiscordId() === (int)$roleData['discordId']
            )->first();

            if (!$role) {
                continue;
            }

            $token->removeDiscordRole($role);
            $this->entityManager->remove($role);
        }
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
                $this->discordRoleManager->removeAllRoles($token);
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

        $this->discordRoleManager->removeAllRoles($token);

        return $this->view(['status' => 'success'], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{tokenName}/roles/update", name="update_discord_roles", options={"expose"=true})
     */
    public function updateRolesFromDiscord(string $tokenName): View
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (!$token->getDiscordConfig()->hasGuild()) {
            throw new ApiBadRequestException();
        }

        $guild = $this->discordManager->getGuild($token);

        $rolesFromDiscord = $this->discordManager->getManageableRoles($guild);

        $showHelp = count($guild->roles) - 2 > count($rolesFromDiscord);

        $currentRoles = $token->getDiscordRoles()->toArray();

        $removedRoles = [];

        foreach ($currentRoles as $key => $role) {
            $discordId = (string)$role->getDiscordId();

            $roleFromDiscord = $rolesFromDiscord[$discordId] ?? null;

            unset($rolesFromDiscord[$discordId]);

            if (!$roleFromDiscord) {
                $removedRoles[] = $role;

                unset($currentRoles[$key]);

                continue;
            }

            $role->update($roleFromDiscord);
        }

        $this->discordRoleManager->removeRoles($removedRoles, false);

        $this->entityManager->flush();

        return $this->view([
            'currentRoles' => $currentRoles,
            'newRoles' => array_values($rolesFromDiscord),
            'showHelp' => $showHelp,
        ], Response::HTTP_OK);
    }
}
