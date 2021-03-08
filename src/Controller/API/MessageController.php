<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\MessageManagerInterface;
use App\Manager\ThreadManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function __construct(
        ThreadManagerInterface $threadManager,
        MessageManagerInterface $messageManager,
        UserActionLogger $userActionLogger,
        TranslatorInterface $translation
    ) {
        $this->threadManager = $threadManager;
        $this->messageManager = $messageManager;
        $this->userActionLogger = $userActionLogger;
        $this->translation = $translation;
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
        MoneyWrapperInterface $moneyWrapper,
        BalanceHandlerInterface $balanceHandler
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        $thread = $this->threadManager->find($threadId);

        if (!$thread || !$thread->hasParticipant($user)) {
            throw new ApiNotFoundException(
                $this->translation->trans('api.not_found')
            );
        }

        $availableBalance = $balanceHandler->balance(
            $user,
            $thread->getToken()
        )->getAvailable();
        $dmMinAmount = (float)$this->getParameter('dm_min_amount');

        if ((float)$moneyWrapper->format($availableBalance) < $dmMinAmount) {
            throw new ApiBadRequestException(
                $this->translation->trans('api.no_enough_amount')
            );
        }

        $messageBody = $request->get('body');
        $this->messageManager->sendMessage($thread, $user, $messageBody);

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
            self::MESSAGES_PAGE_LIMIT,
            ($page -1) * self::MESSAGES_PAGE_LIMIT
        );

        $this->messageManager->setRead($thread, $user);

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

        $this->messageManager->setRead($thread, $user);

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
}
