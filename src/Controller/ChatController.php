<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Logger\UserActionLogger;
use App\Manager\ThreadManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/chat")
 */
class ChatController extends Controller
{
    private TokenManagerInterface $tokenManager;
    private ThreadManagerInterface $threadManager;
    private BalanceHandlerInterface $balanceHandler;
    private MoneyWrapperInterface $moneyWrapper;
    private UserActionLogger $userActionLogger;

    public function __construct(
        NormalizerInterface $normalizer,
        TokenManagerInterface $tokenManager,
        ThreadManagerInterface $threadManager,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        UserActionLogger $userActionLogger
    ) {
        parent::__construct($normalizer);
        $this->tokenManager = $tokenManager;
        $this->threadManager = $threadManager;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->userActionLogger = $userActionLogger;
    }

    /** @Route("/{threadId<\d+>}", name="chat", defaults={"threadId"="0"}, options={"expose"=true}) */
    public function messages(int $threadId): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $thread = $this->threadManager->find($threadId);

        if ($threadId > 0 &&
            (!$thread || !$thread->hasParticipant($user))
        ) {
            throw new NotFoundHttpException();
        }

        $threads = $this->threadManager->traderThreads($user);

        return $this->render('pages/chat.html.twig', [
            'threads' => $this->normalize($threads),
            'threadId' => $threadId,
            'dMMinAmount' => (float)$this->getParameter('dm_min_amount'),
            'precision' => $this->getParameter('token_precision'),
            'hash' => $user->getHash(),
        ]);
    }

    /** @Route("/new/{tokenName}", name="new_dm_message", options={"expose"=true}) */
    public function newDMMessage(string $tokenName): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new NotFoundHttpException();
        }

        if ($user->getId() === $token->getOwner()->getId()) {
            throw new NotFoundHttpException();
        }

        $balance = $this->balanceHandler->balance($user, $token)->getAvailable();

        if ((float)$this->moneyWrapper->format($balance) < (float)$this->getParameter('dm_min_amount')) {
            throw new NotFoundHttpException();
        }

        $thread = $this->threadManager->firstOrNewDMThread($token, $user);

        $this->userActionLogger->info(
            "start chat user: {$user->getUsername()}, token: {$thread->getToken()->getName()}"
        );

        return $this->redirectToRoute('chat', [
            'threadId' => $thread->getId(),
        ]);
    }
}
