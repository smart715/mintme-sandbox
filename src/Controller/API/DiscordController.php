<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Activity\ActivityTypes;
use App\Communications\DiscordOAuthClientInterface;
use App\Controller\TokenSettingsController;
use App\Controller\Traits\ViewOnlyTrait;
use App\Discord\SlashCommandsHandlerInterface;
use App\Entity\DiscordRole;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\Activity\TokenEventActivity;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Form\DiscordRoleType;
use App\Manager\DiscordConfigManager;
use App\Manager\DiscordManagerInterface;
use App\Manager\DiscordRoleManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use Discord\InteractionResponseType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use GuzzleHttp\Command\Exception\CommandClientException;
use Psr\Log\LoggerInterface;
use RestCord\DiscordClient;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    private LoggerInterface $logger;
    private SlashCommandsHandlerInterface $slashCommandsHandler;
    private EventDispatcherInterface $eventDispatcher;
    protected SessionInterface $session;

    use ViewOnlyTrait;

    public function __construct(
        TokenManagerInterface $tokenManager,
        DiscordManagerInterface $discordManager,
        DiscordRoleManagerInterface $discordRoleManager,
        DiscordConfigManager $discordConfigManager,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        DiscordOAuthClientInterface $discordOAuthClient,
        LoggerInterface $logger,
        SlashCommandsHandlerInterface $slashCommandsHandler,
        EventDispatcherInterface $eventDispatcher,
        SessionInterface $session
    ) {
        $this->tokenManager = $tokenManager;
        $this->discordManager = $discordManager;
        $this->discordRoleManager = $discordRoleManager;
        $this->discordConfigManager = $discordConfigManager;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->discordOAuthClient = $discordOAuthClient;
        $this->logger = $logger;
        $this->slashCommandsHandler = $slashCommandsHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->session = $session;
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
     * @throws ApiBadRequestException
     */
    public function manageRoles(string $tokenName, ParamFetcherInterface $request): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

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

        if (count($newRolesData)) {
            $this->eventDispatcher->dispatch(
                new TokenEventActivity($token, ActivityTypes::DISCORD_REWARDS_ADDED),
                TokenEventActivity::NAME
            );
        }

        $this->entityManager->persist($token);

        try {
            $this->entityManager->flush();

            $this->discordManager->updateRolesOfUsers($token);
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
            $this->discordManager->removeAllGuildMembersRole($token, $role);
            $this->entityManager->remove($role);
        }
    }

    /**
     * @Rest\Get("/callback/user", name="discord_callback_user")
     * @Rest\QueryParam(name="code")
     */
    public function userCallback(ParamFetcherInterface $request): RedirectResponse
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $code = $request->get('code');

        if (!$code) {
            return $this->redirectToRoute(
                'settings',
                ['_locale' => $this->session->get('locale_lang')]
            );
        }

        $redirectUrl = $this->generateUrl(
            'discord_callback_user',
            ['_locale' => 'en'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

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

        return $this->redirectToRoute(
            'settings',
            ['_locale' => $this->session->get('locale_lang')]
        );
    }

    /**
     * @Rest\Get("/callback/bot", name="discord_callback_bot")
     * @Rest\QueryParam(name="guild_id")
     * @Rest\QueryParam(name="state")
     * @Rest\QueryParam(name="permissions")
     */
    public function botCallback(ParamFetcherInterface $request): RedirectResponse
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $guildId = (int)$request->get('guild_id');
        $permissions = (int)$request->get('permissions');
        $tokenId = (int)$request->get('state');

        $token = $this->tokenManager->findById($tokenId);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if ($this->discordConfigManager->findByGuildId($guildId)) {
            $this->addFlash('danger', $this->translator->trans('discord.error.guild_already_used'));
        } elseif ($guildId && DiscordOAuthClientInterface::BOT_PERMISSIONS_ADMINISTRATOR === $permissions) {
            $config = $token->getDiscordConfig();

            if ($config->hasGuild() && $guildId !== $config->getGuildId()) {
                $this->discordRoleManager->removeAllRoles($token);
            }

            $config->setGuildId($guildId)
                   ->setEnabled(true)
                   ->setSpecialRolesEnabled(true);

            $this->entityManager->persist($config);
            $this->entityManager->flush();
        }

        $this->session->set(TokenSettingsController::SHOW_DISCORD_TAB_SESSION_NAME, true);

        return $this->redirectToRoute('token_settings', [
            'tokenName' => $token->getName(),
            'tab' => 'promotion',
            'sub' => 'discord_rewards',
            '_locale' => $this->session->get('locale_lang'),
        ]);
    }

    /**
     * @Rest\View
     * @Rest\Post("/interaction", name="discord_interaction")
     */
    public function interaction(Request $request): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $body = $request->getContent();

        $headers = $request->headers;

        $signature = $headers->get('X-SIGNATURE-ED25519');
        $timestamp = $headers->get('X-SIGNATURE-TIMESTAMP');

        $this->logger->error('discord interaction', [
            'body' => $body,
            'signature' => $signature,
            'timestamp' => $timestamp,
        ]);

        $isInteractionValid = $this->discordManager->verifyInteraction($body, $signature, $timestamp);

        if (!$isInteractionValid) {
            return $this->view(['message' => 'invalid request signature'], Response::HTTP_UNAUTHORIZED);
        }

        $params = \json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (InteractionResponseType::PONG === $params['type']) {
            return $this->view(['type' => InteractionResponseType::PONG], Response::HTTP_OK);
        }

        return $this->view($this->slashCommandsHandler->handleInteraction($params), Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Delete("/{tokenName}/guild", name="remove_guild", options={"expose"=true})
     */
    public function removeGuild(string $tokenName): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $token);

        try {
            $this->discordManager->leaveGuild($token);
        } catch (CommandClientException $ex) {
            // If guild does not exists or bot was kicked
            if (Response::HTTP_NOT_FOUND !== $ex->getResponse()->getStatusCode()) {
                throw $ex;
            }
        }

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
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

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
