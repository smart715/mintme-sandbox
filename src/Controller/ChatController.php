<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Message\ThreadMetadata;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Logger\UserActionLogger;
use App\Manager\ThreadManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TopHolderManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Exception\NotEnoughAmountException;
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
    private TopHolderManagerInterface $topHolderManager;

    public function __construct(
        NormalizerInterface $normalizer,
        TokenManagerInterface $tokenManager,
        ThreadManagerInterface $threadManager,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        UserActionLogger $userActionLogger,
        TopHolderManagerInterface $topHolderManager
    ) {
        parent::__construct($normalizer);
        $this->tokenManager = $tokenManager;
        $this->threadManager = $threadManager;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->userActionLogger = $userActionLogger;
        $this->topHolderManager = $topHolderManager;
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

        if ($thread) {
            $metaData = $thread->getMetadata();

            /** @var ThreadMetadata $data */
            foreach ($metaData as $data) {
                if ($data->getParticipant()->getId() != $user->getId() && $data->getIsBlocked()) {
                    return $this->redirectToRoute('chat');
                }
            }

            $this->threadManager->showHiddenThread($metaData, $user);
        }

        $threads = $this->threadManager->traderThreads($user);
        $topHolders = $this->topHolderManager->getOwnTopHolders();

        return $this->render('pages/chat.html.twig', [
            'threads' => $this->normalize($threads),
            'threadId' => $threadId,
            'dMMinAmount' => $thread ? (float)$thread->getToken()->getDmMinAmount() : 0,
            'precision' => $this->getParameter('token_precision'),
            'hash' => $user->getHash(),
            'topHolders' => $this->normalize($topHolders),
        ]);
    }

    /** @Route("/new/{tokenName}", name="new_dm_message", options={"expose"=true}) */
    public function newDMMessage(string $tokenName): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $token = $this->tokenManager->findByName($tokenName);

        if (!$token || $user->getId() === $token->getOwner()->getId()) {
            throw new NotFoundHttpException();
        }

        $fullAvailableBalance = $this->balanceHandler->balance($user, $token)->getFullAvailable();
        $dmMinAmount = $this->moneyWrapper->parse(
            $token->getDmMinAmount(),
            Symbols::TOK
        );

        if ($fullAvailableBalance->lessThan($dmMinAmount)) {
            throw new NotEnoughAmountException();
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
