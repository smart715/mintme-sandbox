<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropAction;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\AirdropEvent;
use App\Events\TokenEvents;
use App\Events\UserAirdropEvent;
use App\Exception\ApiBadForbiddenException;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiUnauthorizedException;
use App\Exception\InvalidTwitterTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\AirdropConfig;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\AirdropReferralCodeManager;
use App\Manager\BlacklistManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TwitterManagerInterface;
use App\Utils\AirdropCampaignActions;
use App\Utils\LockFactory;
use App\Utils\Symbols;
use App\Utils\Validator\AirdropCampaignActionsValidator;
use App\Utils\Verify\WebsiteVerifierInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Money;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/airdrop_campaign")
 */
class AirdropCampaignController extends AbstractFOSRestController
{
    private TokenManagerInterface $tokenManager;
    private AirdropCampaignManagerInterface $airdropCampaignManager;
    private AirdropConfig $airdropConfig;
    private TranslatorInterface $translator;
    private TwitterManagerInterface $twitterManager;
    private BlacklistManagerInterface $blacklistManager;
    private LockFactory $lockFactory;
    private EventDispatcherInterface $eventDispatcher;
    private AirdropReferralCodeManager $arcManager;
    private SessionInterface $session;

    public function __construct(
        TokenManagerInterface $tokenManager,
        AirdropCampaignManagerInterface $airdropCampaignManager,
        AirdropConfig $airdropConfig,
        TranslatorInterface $translator,
        TwitterManagerInterface $twitterManager,
        BlacklistManagerInterface $blacklistManager,
        LockFactory $lockFactory,
        EventDispatcherInterface $eventDispatcher,
        AirdropReferralCodeManager $arcManager,
        SessionInterface $session
    ) {
        $this->tokenManager = $tokenManager;
        $this->airdropCampaignManager = $airdropCampaignManager;
        $this->airdropConfig = $airdropConfig;
        $this->translator = $translator;
        $this->twitterManager = $twitterManager;
        $this->blacklistManager = $blacklistManager;
        $this->lockFactory = $lockFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->arcManager = $arcManager;
        $this->session = $session;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/domain-blacklist-check", name="airdrop_domain_blacklist_check", options={"expose"=true})
     * @Rest\QueryParam(name="domain", allowBlank=false)
     * @param ParamFetcherInterface $request
     * @return View
     */
    public function checkDomainBlacklistAction(ParamFetcherInterface $request): View
    {
        return $this->view(
            ['blacklisted' => $this->blacklistManager->isBlacklistedAirdropDomain($request->get('domain'))],
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{tokenName}", name="get_airdrop_campaign", options={"expose"=true})
     * @param string $tokenName
     * @return View
     * @throws ApiBadRequestException
     */
    public function getAirdropCampaign(string $tokenName): View
    {
        $token = $this->fetchToken($tokenName);

        $airdrop = $token->getActiveAirdrop();

        /** @var User|null $user */
        $user = $this->getUser();

        $referralCode = null;

        if ($user && $airdrop && $airdrop->getToken()->getOwner()->getId() !== $user->getId()) {
            $referralCode = $this->arcManager->getByAirdropAndUser($airdrop, $user)
                ?? $this->arcManager->create($airdrop, $user);
            $referralCode = $this->arcManager->encode($referralCode);
        }

        return $this->view(['airdrop' => $airdrop, "referral_code" => $referralCode], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/create", name="create_airdrop_campaign", options={"expose"=true})
     * @Rest\RequestParam(
     *     name="amount",
     *     allowBlank=false,
     *     description="Amount of tokens."
     * )
     * @Rest\RequestParam(
     *     name="participants",
     *     allowBlank=false,
     *     description="Amount of participants."
     * )
     * @Rest\RequestParam(
     *     name="endDate",
     *     allowBlank=true,
     *     nullable=true,
     *     description="Airdrop campaign end date timestamp."
     * )
     */
    public function createAirdropCampaign(
        string $tokenName,
        MoneyWrapperInterface $moneyWrapper,
        BalanceHandlerInterface $balanceHandler,
        Request $request
    ): View {

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new ApiUnauthorizedException();
        }

        $lock = $this->lockFactory->createLock(LockFactory::LOCK_BALANCE.$user->getId());

        if (!$lock->acquire()) {
            throw new AccessDeniedException();
        }

        $token = $this->fetchToken($tokenName, true);

        if ($token->getActiveAirdrop()) {
            throw new ApiBadRequestException($this->translator->trans('airdrop_backend.already_has_active_airdrop'));
        }

        $amount = $moneyWrapper->parse((string)$request->get('amount'), Symbols::TOK);
        $participants = (int)$request->get('participants');
        $endDateTimestamp = (int)$request->get('endDate');
        $balance = $balanceHandler->exchangeBalance(
            $token->getProfile()->getUser(),
            $token
        );

        $this->checkAirdropParams($amount, $participants, $balance);
        $endDateTimestamp = $this->checkAirdropEndDate($endDateTimestamp);

        $actions = $request->get('actions');
        $actionsData = $request->get('actionsData');

        if (!is_array($actions)) {
            $actions = null;
        }

        if (!is_array($actionsData)) {
            $actionsData = [];
        }

        $actionsValidator = new AirdropCampaignActionsValidator($actions, $actionsData, $token);

        if (!$actionsValidator->validate()) {
            throw new ApiBadRequestException($this->translator->trans($actionsValidator->getMessage()));
        }

        $endDate = $endDateTimestamp
            ? (new \DateTimeImmutable())->setTimestamp($endDateTimestamp)
            : null;

        $airdrop = $this->airdropCampaignManager->createAirdrop(
            $token,
            $amount,
            $participants,
            $endDate
        );

        $actionsData = $this->transformData($actions, $actionsData, $airdrop);

        foreach ($actions as $action => $active) {
            if ($active) {
                $this->airdropCampaignManager->createAction($action, $actionsData[$action] ?? null, $airdrop);
            }
        }

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(new AirdropEvent($airdrop), TokenEvents::AIRDROP_CREATED);

        $lock->release();

        return $this->view([
            'id' => $airdrop->getId(),
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Delete(
     *     "/{id}/delete",
     *     name="delete_airdrop_campaign",
     *     options={"expose"=true},
     *     requirements={"id"="\d+"}
     * )
     */
    public function deleteAirdropCampaign(Airdrop $airdrop): View
    {
        $this->denyAccessUnlessGranted('edit', $airdrop->getToken());

        if ($airdrop->isActive()) {
            $this->airdropCampaignManager->deleteAirdrop($airdrop);
        }

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/{id}/claim", name="claim_airdrop_campaign", options={"expose"=true})
     */
    public function claimAirdropCampaign(string $tokenName, Airdrop $airdrop): View
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $token = $this->fetchToken($tokenName, false, true);

        if (!$token->getActiveAirdrop()) {
            throw new ApiBadRequestException($this->translator->trans('airdrop_backend.nonexistent_campaign'));
        }

        if ($token->getActiveAirdrop()->getId() !== $airdrop->getId()) {
            throw new ApiBadRequestException($this->translator->trans('airdrop_backend.finished'));
        }

        if (!is_null($token->getActiveAirDrop()->getEndDate())) {
            if ($token->getActiveAirdrop()->getEndDate()->getTimeStamp() < time()) {
                throw new ApiBadRequestException($this->translator->trans('airdrop_backend.time_elapsed'));
            }
        }

        if ($this->airdropCampaignManager->checkIfUserClaimed($user, $token)) {
            throw new ApiBadRequestException($this->translator->trans('airdrop_backend.already_claimed'));
        }

        if (!$this->airdropCampaignManager->checkIfUserCompletedActions($airdrop, $user)) {
            throw new ApiBadRequestException($this->translator->trans('airdrop_backend.actions_not_completed'));
        }

        if (!$this->isGranted('claim', $airdrop)) {
            return $this->view(['error' => true], Response::HTTP_OK);
        }

        $this->airdropCampaignManager->claimAirdropCampaign(
            $user,
            $token
        );

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new UserAirdropEvent($airdrop, $user),
            TokenEvents::AIRDROP_CLAIMED
        );

        return $this->view(null, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/action/{id}/claim", name="claim_airdrop_action", options={"expose"=true})
     */
    public function claimAirdropAction(string $tokenName, AirdropAction $action): View
    {
        $this->fetchToken($tokenName, false, true);

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new ApiUnauthorizedException();
        }

        $lock = $this->lockFactory->createLock(LockFactory::LOCK_BALANCE.$user->getId());

        if (!$lock->acquire()) {
            throw new AccessDeniedException();
        }

        $this->airdropCampaignManager->claimAirdropAction($action, $user);

        $lock->release();

        return $this->view(null, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/action/save",
     *     name="claim_airdrop_action_for_guest_user",
     *     options={"expose"=true}
     *     )
     * @Rest\RequestParam(
     *     name="tokenName",
     *     allowBlank=false,
     *     description="token name of airdrop"
     * )
     * @Rest\RequestParam(
     *     name="actionId",
     *     allowBlank=false,
     *     description="id of airdrop action"
     * )
     * @return View
     * @param ParamFetcherInterface $request
     */
    public function storeAirdropTaskCompleted(ParamFetcherInterface $request): View
    {
        $tokenName = $request->get('tokenName');
        $actionId = $request->get('actionId');

        $airdropTasksCompleted =  $this->session->get('airdrops', []);

        if (!array_key_exists($tokenName, $airdropTasksCompleted)) {
            $airdropTasksCompleted[$tokenName] = [];
        }

        if (!in_array($actionId, $airdropTasksCompleted[$tokenName], true)) {
            $airdropTasksCompleted[$tokenName][] = $actionId;
        }

        $this->session->set('airdrops', $airdropTasksCompleted);

        return $this->view(null, Response::HTTP_OK);
    }


    /**
     * @Rest\View()
     * @Rest\Get("/{tokenName}/completed-actions", name="get_airdrop_completed_actions", options={"expose"=true})
     * @param string $tokenName
     * @return View
     */
    public function getAirdropCompletedActions(string $tokenName): View
    {
        $airdropCompletedActions = $this->session->get('airdrops', []);

        return $this->view(
            $airdropCompletedActions[$tokenName] ?? [],
            Response::HTTP_OK
        );
    }



    /**
     * @Rest\View()
     * @Rest\Post("{tokenName}/action/post-link/verify", name="verify_post_link_action", options={"expose"=true})
     * @Rest\RequestParam(
     *     name="url",
     *     allowBlank=false,
     *     description="Url to inspect."
     * )
     */
    public function verifyPostLinkAction(
        string $tokenName,
        ParamFetcherInterface $request,
        WebsiteVerifierInterface $websiteVerifier
    ): View {
        $this->fetchToken($tokenName, false, true);
        $message = $this->generateUrl('token_show', ['name' => $tokenName], UrlGeneratorInterface::ABSOLUTE_URL);

        $url = $request->get('url');
        $validator = Validation::createValidator();

        $errors = $validator->validate($url, new Url());

        if (count($errors) > 0) {
            throw new ApiBadRequestException($this->translator->trans('airdrop_backend.invalid_url'));
        }

        if ($this->blacklistManager->isBlacklistedAirdropDomain($url)) {
            throw new ApiBadForbiddenException($this->translator->trans('api.airdrop.forbidden_domain', [
                '%domain%' => $url,
            ]));
        }

        $verified = $websiteVerifier->verifyAirdropPostLinkAction($url, $message);

        return $this->view(['verified' => $verified], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("{tokenName}/share/twitter", name="airdrop_share_twitter", options={"expose"=true})
     */
    public function shareOnTwitter(string $tokenName): View
    {
        $token = $this->fetchToken($tokenName, false, true);

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new ApiUnauthorizedException();
        }

        $airdrop = $token->getActiveAirdrop();

        $arc = $this->arcManager->getByAirdropAndUser($airdrop, $user) ?? $this->arcManager->create($airdrop, $user);
        $hash = $this->arcManager->encode($arc);

        $url = $this->generateUrl('airdrop_referral', ['name' => $tokenName, 'hash' => $hash], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = $this->translator->trans('ongoing_airdrop.actions.message', [
            '%tokenName%' => $tokenName,
            '%tokenUrl%' => $url,
        ]);

        try {
            $this->twitterManager->sendTweet($user, $message);
        } catch (InvalidTwitterTokenException $e) {
            throw new ApiBadRequestException($e->getMessage());
        } catch (\Throwable $e) {
            throw new \Exception($this->translator->trans('api.something_went_wrong'));
        }

        return $this->view(['message' => $this->translator->trans('api.success')], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("{tokenName}/action/{id}/retweet", name="retweet_action", options={"expose"=true})
     */
    public function retweetAction(string $tokenName, AirdropAction $action): View
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new ApiUnauthorizedException();
        }

        $token = $this->fetchToken($tokenName, false, true);

        if ($action->getAirdrop()->getToken() !== $token
            || $action->getType() !== AirdropAction::TYPE_MAP['twitterRetweet']
        ) {
            throw new ApiBadRequestException();
        }

        try {
            $this->twitterManager->retweet($user, $action->getData());
        } catch (InvalidTwitterTokenException $e) {
            throw new ApiBadRequestException($e->getMessage());
        } catch (\Throwable $e) {
            throw new \Exception($this->translator->trans('api.something_went_wrong'));
        }

        return $this->view();
    }

    private function checkAirdropParams(Money $amount, int $participants, Money $balance): void
    {
        if ($amount->lessThan($this->airdropConfig->getMinTokensAmount()) || $amount->greaterThan($balance)) {
            throw new ApiBadRequestException($this->translator->trans('airdrop_backend.invalid_amount'));
        }

        $reward = $amount->divide($participants);

        if ($reward->lessThan($this->airdropConfig->getMinTokenReward())) {
            throw new ApiBadRequestException(
                $this->translator->trans('airdrop_backend.invalid_reward')
            );
        }

        if ($participants < $this->airdropConfig->getMinParticipantsAmount()
            || $participants > $this->airdropConfig->getMaxParticipantsAmount()
        ) {
            throw new ApiBadRequestException($this->translator->trans('airdrop_backend.invalid_participants_amount'));
        }
    }

    private function checkAirdropEndDate(?int $endDateTimestamp): ?int
    {
        $timeAfterOneHour = time() + 60 * 60;

        return $endDateTimestamp && $endDateTimestamp < $timeAfterOneHour
            ? $timeAfterOneHour
            : $endDateTimestamp;
    }

    private function fetchToken(
        string $tokenName,
        bool $checkIfOwner = false,
        bool $checkIfParticipant = false
    ): Token {
        /** @var Token|null $token */
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        if ($checkIfOwner) {
            $this->denyAccessUnlessGranted('edit', $token);
        }

        if ($checkIfParticipant && $token === $this->tokenManager->getOwnMintmeToken()) {
            throw new ApiBadRequestException($this->translator->trans('airdrop_backend.own_airdrop'));
        }

        return $token;
    }


    private function transformData(array $actions, array $actionsData, Airdrop $airdrop): array
    {
        if ($actions[AirdropCampaignActions::TWITTER_RETWEET]) {
            $matches = [];

            preg_match(
                '/^(?:https?:\/\/)?(?:www\.)?twitter\.com\/[\S]+\/status\/([\d]+)$/',
                $actionsData[AirdropCampaignActions::TWITTER_RETWEET],
                $matches
            );

            $actionsData[AirdropCampaignActions::TWITTER_RETWEET] = $matches[1];
        }

        if ($actions[AirdropCampaignActions::YOUTUBE_SUBSCRIBE]) {
            $actionsData[AirdropCampaignActions::YOUTUBE_SUBSCRIBE] = $airdrop->getToken()->getYoutubeChannelId();
        }

        if ($actions[AirdropCampaignActions::FACEBOOK_PAGE]) {
            $actionsData[AirdropCampaignActions::FACEBOOK_PAGE] = $airdrop->getToken()->getFacebookUrl();
        }

        return $actionsData;
    }
}
