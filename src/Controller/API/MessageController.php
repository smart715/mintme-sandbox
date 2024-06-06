<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Activity\ActivityTypes;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\Message\Thread;
use App\Entity\Message\ThreadMetadata;
use App\Entity\User;
use App\Events\Activity\UserTokenEventActivity;
use App\Events\TokenEvents;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\MessageManagerInterface;
use App\Manager\ThreadManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Rest\Route("/api/message")
 */
class MessageController extends AbstractFOSRestController
{
    private const MESSAGES_PAGE_LIMIT = 100;
    private ThreadManagerInterface $threadManager;
    private MessageManagerInterface $messageManager;
    private UserActionLogger $userActionLogger;
    private TranslatorInterface $translation;
    private MoneyWrapperInterface $moneyWrapper;
    private EventDispatcherInterface $eventDispatcher;
    protected SessionInterface $session;

    use ViewOnlyTrait;

    public function __construct(
        ThreadManagerInterface $threadManager,
        MessageManagerInterface $messageManager,
        UserActionLogger $userActionLogger,
        TranslatorInterface $translation,
        MoneyWrapperInterface $moneyWrapper,
        SessionInterface $session,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->threadManager = $threadManager;
        $this->messageManager = $messageManager;
        $this->userActionLogger = $userActionLogger;
        $this->translation = $translation;
        $this->moneyWrapper = $moneyWrapper;
        $this->eventDispatcher = $eventDispatcher;
        $this->session = $session;
    }

    /**
     * @Rest\View()
     * @Rest\POST("/dm/send/{threadId}", name="send_dm_message", options={"expose"=true})
     * @Rest\RequestParam(name="threadId", nullable=false, requirements="\d+")
     * @Rest\RequestParam(name="body", nullable=false)
     */
    public function sendDMMessage(
        int $threadId,
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        $thread = $this->threadManager->find($threadId);

        if (!$thread || !$thread->hasParticipant($user)) {
            throw new ApiNotFoundException(
                $this->translation->trans('api.not_found')
            );
        }

        $metadata = $thread->getMetadata();

        /** @var ThreadMetadata $item */
        foreach ($metadata as $item) {
            if ($item->getParticipant()->getId() === $user->getId() && $item->getIsBlocked()) {
                return $this->view([
                    'status' => 'blocked',
                    'message' => $this->translation->trans('chat.block_message'),
                ], Response::HTTP_OK);
            }

            if ($item->getParticipant()->getId() !== $user->getId() && $item->isHidden()) {
                $this->threadManager->toggleHiddenThread($metadata, $item->getParticipant());
            }
        }

        $fullAvailableBalance = $balanceHandler->balance($user, $thread->getToken())->getFullAvailable();
        $dmMinAmount = $this->moneyWrapper->parse(
            $thread->getToken()->getDmMinAmount(),
            Symbols::TOK
        );

        if (!$this->isTokenCreator($thread, $user) && $fullAvailableBalance->lessThan($dmMinAmount)) {
            throw new ApiBadRequestException(
                $this->translation->trans('api.no_enough_amount')
            );
        }

        $messageBody = $request->get('body');
        $this->messageManager->sendMessage($thread, $user, $messageBody);

        $this->eventDispatcher->dispatch(
            new UserTokenEventActivity($user, $thread->getToken(), ActivityTypes::TOKEN_NEW_DM),
            TokenEvents::NEW_DM
        );

        $this->userActionLogger->info(
            "send a message user: {$user->getUsername()}, token: {$thread->getToken()->getName()}, message: {$messageBody}"
        );

        return $this->view([], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\GET(
     *     "/list/{threadId}/{page}",
     *      name="get_messages",
     *      requirements={"threadId"="^\d+$", "page"="^\d+$"},
     *      defaults={"page"=1},
     *      options={"expose"=true}
     *     )
     */
    public function getMessages(
        int $threadId,
        int $page
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        $thread = $this->threadManager->find($threadId);

        if (!$thread || !$thread->hasParticipant($user)) {
            throw new ApiNotFoundException(
                $this->translation->trans('api.not_found')
            );
        }

        $messages = $this->messageManager->getMessages(
            $thread,
            $user,
            self::MESSAGES_PAGE_LIMIT,
            ($page -1) * self::MESSAGES_PAGE_LIMIT
        );

        if (!$this->isViewOnly()) {
            $this->messageManager->setRead($thread, $user);
        }

        return $this->view($messages, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\GET(
     *     "/new/{threadId}/{lastMessageId}",
     *      name="get_new_messages",
     *      requirements={"threadId"="^\d+$", "lastMessageId"="^\d+$"},
     *      defaults={"page"=1},
     *      options={"expose"=true}
     *     )
     */
    public function getNewMessages(
        int $threadId,
        int $lastMessageId
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        $thread = $this->threadManager->find($threadId);

        if (!$thread || !$thread->hasParticipant($user)) {
            throw new ApiNotFoundException(
                $this->translation->trans('api.not_found')
            );
        }

        $messages = $this->messageManager->getNewMessages(
            $thread,
            $lastMessageId
        );

        if (!$this->isViewOnly()) {
            $this->messageManager->setRead($thread, $user);
        }

        return $this->view($messages, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\GET(
     *     "/unread/count",
     *      name="get_unread_messages_count",
     *      options={"expose"=true}
     *     )
     */
    public function getUnreadCount(): View
    {
        /** @var User $user */
        $user = $this->getUser();

        $count = $this->messageManager->getUnreadCount($user);

        return $this->view($count, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\GET(
     *     "/{threadId<^[1-9][0-9]*$>}/market",
     *      name="get_thread_market",
     *      options={"expose"=true}
     *     )
     */
    public function getThreadMarket(
        int $threadId,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketManager
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        $thread = $this->threadManager->find($threadId);

        if ((!$thread || !$thread->hasParticipant($user))) {
            throw new ApiNotFoundException(
                $this->translation->trans('api.not_found')
            );
        }

        $webCrypto = $cryptoManager->findBySymbol(Symbols::WEB);

        if (!$webCrypto) {
            throw new ApiNotFoundException(
                $this->translation->trans('api.not_found')
            );
        }

        return $this->view($marketManager->create($webCrypto, $thread->getToken()), Response::HTTP_OK);
    }

    private function isTokenCreator(Thread $thread, User $user): bool
    {
        return $thread->getToken()->getProfile()->getUser()->getId() === $user->getId();
    }
}
