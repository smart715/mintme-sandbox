<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Activity\ActivityTypes;
use App\Config\RewardsConfig;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardMemberInterface;
use App\Entity\Rewards\RewardParticipant;
use App\Entity\Rewards\RewardVolunteer;
use App\Entity\User;
use App\Events\Activity\RewardEventActivity;
use App\Events\RewardEvent;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Form\RewardMemberType;
use App\Form\RewardType;
use App\Manager\CryptoManagerInterface;
use App\Manager\RewardManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\RewardParticipantRepository;
use App\Repository\RewardVolunteerRepository;
use App\Security\RewardVoter;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\LockFactory;
use App\Wallet\Exception\NotEnoughAmountException;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Rest\Route("/api/rewards", condition="%feature_rewards_enabled%")
 */
class RewardController extends APIController
{

    private ParamFetcherInterface $paramFetcher;
    private RewardManagerInterface $rewardManager;
    private TranslatorInterface $translator;
    private EventDispatcherInterface $eventDispatcher;
    private LockFactory $lockFactory;
    private BalanceHandlerInterface $balanceHandler;
    private LoggerInterface $logger;
    private RewardsConfig $rewardsConfig;
    protected SessionInterface $session;
    private RewardVolunteerRepository $volunteerRepository;
    private RewardParticipantRepository $participantRepository;

    use ViewOnlyTrait;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MarketFactoryInterface $marketFactory,
        ParamFetcherInterface $paramFetcher,
        RewardManagerInterface $rewardManager,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        LockFactory $lockFactory,
        BalanceHandlerInterface $balanceHandler,
        LoggerInterface $logger,
        RewardsConfig $rewardsConfig,
        SessionInterface $session,
        RewardVolunteerRepository $volunteerRepository,
        RewardParticipantRepository $participantRepository
    ) {
        parent::__construct($cryptoManager, $tokenManager, $marketFactory);
        $this->paramFetcher = $paramFetcher;
        $this->rewardManager = $rewardManager;
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
        $this->lockFactory = $lockFactory;
        $this->balanceHandler = $balanceHandler;
        $this->logger = $logger;
        $this->rewardsConfig = $rewardsConfig;
        $this->session = $session;
        $this->volunteerRepository = $volunteerRepository;
        $this->participantRepository = $participantRepository;
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/add/{tokenName}/{type}",
     *     requirements={"type"="^(reward|bounty)$"},
     *     name="add_new_reward",
     *     options={"expose"=true}
     * )
     * @Rest\RequestParam(name="title", allowBlank=false, nullable=false)
     * @Rest\RequestParam(name="price", allowBlank=false, nullable=false)
     * @Rest\RequestParam(name="description", allowBlank=true, nullable=true)
     * @Rest\RequestParam(name="quantity", allowBlank=false, nullable=false)
     */
    public function addReward(string $tokenName, string $type): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $this->validateProperties([$this->paramFetcher->get('quantity')], [$this->paramFetcher->get('price')]);

        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        $rewardsCount = count($this->rewardManager->getUnfinishedRewardsByToken($token)[$type]);

        if ($rewardsCount >= $this->rewardsConfig->getMaxLimit($type)) {
            throw new ApiBadRequestException(Reward::TYPE_REWARD === $type
                ? $this->translator->trans('rewards_bounty.max_rewards_limit_reached', ['%amount%' => $rewardsCount])
                : $this->translator->trans('rewards_bounty.max_bounty_limit_reached', ['%amount%' => $rewardsCount]));
        }

        $reward = new Reward();
        $reward
            ->setType($type)
            ->setToken($token);

        $this->denyAccessUnlessGranted('add', $reward);

        return $this->handleRewardForm($reward);
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/edit/{slug}",
     *     name="edit_reward",
     *     options={"expose"=true}
     * )
     * @Rest\RequestParam(name="title", allowBlank=false, nullable=false)
     * @Rest\RequestParam(name="price", allowBlank=false, nullable=false)
     * @Rest\RequestParam(name="description", allowBlank=true, nullable=true)
     * @Rest\RequestParam(name="quantity", allowBlank=false, nullable=false)
     */
    public function editReward(string $slug): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $reward = $this->rewardManager->getBySlug($slug);

        if (!$reward || $reward->isFinishedReward()) {
            throw new ApiNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $reward);

        return $this->handleRewardForm($reward, false);
    }

    /**
     * @Rest\View
     * @Rest\Post("/{rewardSlug}/add/member", name="reward_add_member", options={"expose"=true})
     * @Rest\RequestParam(name="note", nullable=true)
     * @throws \Throwable
     */
    public function addMember(string $rewardSlug): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        $reward = $this->rewardManager->getBySlug($rewardSlug);

        if (!$reward || $reward->isQuantityReached()) {
            return $this->view([], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(RewardVoter::ADD_MEMBER, $reward);
        $this->denyAccessUnlessGranted('interact', $reward->getToken());

        return $this->handleAddMemberForm($reward, $user);
    }

    /**
     * @Rest\View
     * @Rest\Post("/{slug}/accept", name="accept_member", options={"expose"=true})
     * @Rest\RequestParam(name="memberId", nullable=false, requirements="\d+")
     */
    public function acceptMember(string $slug): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $reward = $this->rewardManager->getBySlug($slug);

        if (!$reward || $reward->isFinishedReward()) {
            return $this->view([], Response::HTTP_NOT_FOUND);
        }

        $member = $this->rewardManager->findMemberById($this->paramFetcher->get('memberId'), $reward);

        if (!$member || ($member instanceof RewardParticipant && $member->isCompleted())) {
            throw new ApiBadRequestException();
        }

        $this->denyAccessUnlessGranted('accept-member', $reward);

        if ($member instanceof RewardParticipant) {
            $reward = $this->rewardManager->completeMember($member);

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new RewardEventActivity($reward, ActivityTypes::BOUNTY_PAID, $member),
                RewardEvent::VOLUNTEER_COMPLETED,
            );
        } elseif ($member instanceof RewardVolunteer) {
            $reward = $this->rewardManager->acceptMember($member);

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new RewardEventActivity($reward, ActivityTypes::BOUNTY_ACCEPTED, $member),
                RewardEvent::VOLUNTEER_ACCEPTED,
            );
        }

        return $this->view(
            [
                'reward' => $reward,
                'message' => $member instanceof RewardVolunteer
                    ? $this->translator->trans('bounties_rewards.manage.member.approved')
                    : $this->translator->trans('bounties_rewards.manage.member.paid'),
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View
     * @Rest\Post("/{slug}/refund_reward", name="refund_reward", options={"expose"=true})
     * @Rest\RequestParam(name="participantId", nullable=false, requirements="\d+")
     */
    public function refundReward(string $slug): View
    {
        /** @var User $user */
        $user = $this->getUser();

        $lock = $this->lockFactory->createLock(LockFactory::LOCK_BALANCE.$user->getId());

        if (!$lock->acquire()) {
            throw new AccessDeniedException();
        }

        $reward = $this->rewardManager->getBySlug($slug);

        if (!$reward) {
            throw new ApiBadRequestException();
        }

        $this->denyAccessUnlessGranted('edit', $reward);

        $participant = $this->participantRepository
            ->findParticipantById($this->paramFetcher->get('participantId'));

        if (!$participant) {
            throw new ApiBadRequestException();
        }

        $rewardPrice = $reward->getPrice();

        $balance = $this->balanceHandler->exchangeBalance($user, $reward->getToken());

        if ($balance->lessThan($rewardPrice)) {
            $lock->release();

            throw new ApiBadRequestException($this->translator->trans('reward_bounty.refund_not_enough_balance'));
        }

        try {
            $this->balanceHandler->beginTransaction();
            $this->rewardManager->refundReward($reward, $participant);

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new RewardEvent($reward, $participant),
                RewardEvent::PARTICIPANT_REFUNDED,
            );
        } catch (\Throwable $exception) {
            $this->balanceHandler->rollback();
            $this->logger->error($exception->getMessage());

            throw new ApiBadRequestException();
        } finally {
            $lock->release();
        }

        return $this->view($reward, Response::HTTP_OK);
    }

    /**
     * @Rest\View
     * @Rest\Delete("/{slug}/member/{memberId}", name="delete_bounty_member", options={"expose"=true})
     */
    public function deleteBountyMember(string $slug, int $memberId): View
    {
        $reward = $this->rewardManager->getBySlug($slug);

        if (!$reward || $reward->isFinishedReward()) {
            throw new ApiBadRequestException();
        }

        $this->denyAccessUnlessGranted('delete', $reward);

        $member = $this->volunteerRepository->findVolunteerById($memberId)
            ?? $this->participantRepository->findParticipantById($memberId);

        if (!$member || ($member instanceof RewardParticipant && $member->isCompleted())) {
            throw new ApiBadRequestException();
        }

        $isVolunteer = $member instanceof RewardVolunteer;

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new RewardEvent($reward, $member),
            $isVolunteer ? RewardEvent::VOLUNTEER_REJECTED : RewardEvent::PARTICIPANT_REJECTED,
        );

        return $this->view(
            $member instanceof RewardVolunteer
                ? $this->rewardManager->rejectVolunteer($member)
                : $this->rewardManager->removeParticipant($member),
            Response::HTTP_OK,
        );
    }

    /**
     * @Rest\View
     * @Rest\Post("/{slug}/participant_status", name="change_participant_status", options={"expose"=true})
     * @Rest\RequestParam(name="participantId", nullable=false, requirements="\d+")
     * @Rest\RequestParam(name="status", nullable=false, requirements="delivered")
     */
    public function changeRewardStatus(ParamFetcherInterface $request, string $slug): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $reward = $this->rewardManager->getBySlug($slug);

        if (!$reward || $reward->isFinishedReward()) {
            throw new ApiBadRequestException();
        }

        $this->denyAccessUnlessGranted('edit', $reward);

        $participant = $this->participantRepository->findParticipantById($request->get('participantId'));

        if (!$participant) {
            throw new ApiBadRequestException();
        }

        $status = $request->get('status');

        $this->rewardManager->setParticipantStatus($participant, $status);

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new RewardEvent($reward, $participant),
            RewardEvent::PARTICIPANT_DELIVERED,
        );

        return $this->view($this->rewardManager->getBySlug($slug), Response::HTTP_OK);
    }

    /**
     * @Rest\View
     * @Rest\Delete("/delete/{slug}", name="delete_reward", options={"expose"=true})
     */
    public function deleteReward(string $slug): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $reward = $this->rewardManager->getBySlug($slug);

        if (!$reward) {
            throw new ApiNotFoundException();
        }

        if ($reward->hasPendingParticipants()) {
            throw new ApiNotFoundException(
                $reward->isBountyType()
                    ? $this->translator->trans('reward_bounty.not_completed_bounty')
                    : $this->translator->trans('reward_bounty.not_completed_reward')
            );
        }

        $this->denyAccessUnlessGranted('delete', $reward);

        $this->rewardManager->deleteReward($reward);

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new RewardEvent($reward, null, $reward->getVolunteers()),
            RewardEvent::REWARD_DELETED
        );

        return $this->view([], Response::HTTP_NO_CONTENT);
    }

    private function handleAddMemberForm(Reward $reward, User $user): View
    {
        $isRewardType = Reward::TYPE_REWARD === $reward->getType();

        /** @var RewardMemberInterface $member */
        $member = $isRewardType
            ? new RewardParticipant()
            : new RewardVolunteer();

        $member
            ->setReward($reward)
            ->setUser($user);

        $form = $this->createForm(
            RewardMemberType::class,
            $member,
            [
                'csrf_protection' => false,
                'data_class' => $isRewardType
                    ? RewardParticipant::class
                    : RewardVolunteer::class,
            ]
        );

        $form->submit($this->paramFetcher->all());

        if ($this->volunteerRepository->findVolunteerByUserAndReward($user, $reward)) {
            return $this->view(
                ['error' => $this->translator->trans('bounty.already_requested')],
                Response::HTTP_OK
            );
        }

        if (!$form->isValid()) {
            return $this->view([], Response::HTTP_BAD_REQUEST);
        }

        try {
            $reward = $this->rewardManager->addMember($member);
        } catch (\Throwable $exception) {
            if ($exception instanceof NotEnoughAmountException) {
                return $this->returnNotEnoughBalance();
            }

            throw $exception;
        }

        if ($member instanceof RewardParticipant) {
            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new RewardEventActivity($reward, ActivityTypes::REWARD_NEW_PARTICIPANT, $member),
                RewardEvent::PARTICIPANT_ADDED,
            );
        }

        if ($member instanceof RewardVolunteer) {
            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new RewardEventActivity($reward, ActivityTypes::REWARD_NEW_VOLUNTEER, $member),
                RewardEvent::VOLUNTEER_NEW,
            );
        }

        return $this->view($reward, Response::HTTP_OK);
    }

    private function handleRewardForm(Reward $reward, bool $newReward = true): View
    {
        $oldPrice = $reward->getPrice();
        $oldQuantity = $reward->getQuantity();

        $form = $this->createForm(RewardType::class, $reward, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);

        $form->submit($this->paramFetcher->all());

        if (!$form->isValid()) {
            foreach ($form->all() as $childForm) {
                /** @var FormError[] $fieldErrors */
                $fieldErrors = $form->get($childForm->getName())->getErrors();

                if (count($fieldErrors) > 0) {
                    return $this->view($fieldErrors[0]->getMessage(), Response::HTTP_BAD_REQUEST);
                }
            }
        }

        if (!$newReward && $reward->isBountyType() && !$reward->getPrice()->equals($oldPrice)) {
            return $this->view(
                $this->translator->trans('rewards_bounty.price_change_error'),
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$newReward && $reward->isBountyType()
            && $reward->getActiveParticipantsAmount() >= $reward->getQuantity()
        ) {
            return $this->view(
                $this->translator->trans('rewards_bounty.quantity_less_then_participants', [
                    '%participantsAmount%' => $reward->getActiveParticipantsAmount(),
                ]),
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            if ($newReward) {
                $this->rewardManager->createReward($reward);
            } else {
                $this->rewardManager->saveReward($reward, $oldPrice, $oldQuantity);
            }
        } catch (\Throwable $exception) {
            if ($exception instanceof NotEnoughAmountException) {
                return $this->returnNotEnoughBalance();
            }

            throw $exception;
        }

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new RewardEventActivity(
                $reward,
                $reward->isBountyType() ? ActivityTypes::BOUNTY_NEW : ActivityTypes::REWARD_NEW,
                null
            ),
            RewardEvent::REWARD_NEW
        );

        return $this->view($reward, Response::HTTP_OK);
    }

    private function returnNotEnoughBalance(): View
    {
        return $this->view(
            ['error' => $this->translator->trans('rewards_bounty.not_enough_balance')],
            Response::HTTP_OK
        );
    }
}
