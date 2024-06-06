<?php declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\PendingWithdrawInterface;
use App\Entity\User;
use App\Logger\WithdrawLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserTokenManagerInterface;
use App\Manager\WithdrawalLocksManager;
use App\Repository\PendingWithdrawRepository;
use App\Security\Config\DisabledBlockchainConfig;
use App\Security\Config\DisabledServicesConfig;
use App\Security\DisabledServicesVoter;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Wallet\WalletInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;

/**
 * @Route("/wallet")
 */
class WalletController extends Controller
{
    private RebrandingConverterInterface $rebrandingConverter;
    private TranslatorInterface $translator;
    protected SessionInterface $session;
    private WithdrawalLocksManager $withdrawalLocksManager;
    private WithdrawLogger $withdrawLogger;

    use ViewOnlyTrait;

    public function __construct(
        NormalizerInterface $normalizer,
        RebrandingConverterInterface $rebrandingConverter,
        TranslatorInterface $translator,
        SessionInterface $session,
        WithdrawalLocksManager $withdrawalLocksManager,
        WithdrawLogger $withdrawLogger
    ) {
        $this->rebrandingConverter = $rebrandingConverter;
        $this->translator = $translator;
        $this->session = $session;
        $this->withdrawalLocksManager = $withdrawalLocksManager;
        $this->withdrawLogger = $withdrawLogger;

        parent::__construct($normalizer);
    }

    /**
     * @Route(
     *     path="/{tab}",
     *     name="wallet",
     *     methods={"GET"},
     *     defaults={"tab"="wallet"},
     *     requirements={"tab"="dw-history|trade-history|active-orders|activity-history"},
     *     options={"expose"=true}
     * )
     * @param Request $request
     * @param DisabledBlockchainConfig $disabledBlockchainConfig
     * @param DisabledServicesConfig $disabledServicesConfig
     * @param string|null $tab
     * @return Response
     */
    public function wallet(
        Request $request,
        DisabledBlockchainConfig $disabledBlockchainConfig,
        DisabledServicesConfig $disabledServicesConfig,
        CryptoManagerInterface $cryptoManager,
        UserTokenManagerInterface $userTokensManager,
        ?string $tab
    ): Response {
        $depositMore = $request->get('depositMore') ?? '';

        /** @var User|null $user*/
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('login');
        }

        return $this->render('pages/wallet.html.twig', [
            'hash' => $user->getHash(),
            'depositMore' => $this->rebrandingConverter->reverseConvert($depositMore),
            'disabledBlockchain' => $disabledBlockchainConfig->getDisabledCryptoSymbols(),
            'disabledServicesConfig' => $this->normalize($disabledServicesConfig),
            'tab' => $tab,
            'enabledCryptos' => $this->normalize($cryptoManager->findAll()),
            'ownTokensCount' => $userTokensManager->getUserOwnsCount($user->getId()),
        ]);
    }

    /**
     * @Route("/withdraw/{hash}",
     *     name="withdraw-confirm",
     *     options={"expose"=true},
     *     schemes={"https"}
     * )
     */
    public function withdrawConfirm(string $hash, WalletInterface $wallet): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user || $this->isViewOnly()) {
            throw new AccessDeniedException();
        }

        $entityManager = $this->getDoctrine()->getManager();

        /** @var PendingWithdrawRepository $withdrawRepo */
        $withdrawRepo = $entityManager->getRepository(PendingWithdraw::class);

        /** @var PendingWithdrawRepository $withdrawTokenRepo */
        $withdrawTokenRepo = $entityManager->getRepository(PendingTokenWithdraw::class);

        /** @var PendingWithdrawInterface|null */
        $pendingWithdraw = $withdrawRepo->getWithdrawByHash($hash) ?? $withdrawTokenRepo->getWithdrawByHash($hash);

        if (!$pendingWithdraw) {
            return $this->createWalletRedirection(
                'danger',
                $this->translator->trans('wallet.no_transaction_attached')
            );
        }

        if (($pendingWithdraw instanceof PendingTokenWithdraw &&
            !$this->isGranted(DisabledServicesVoter::TOKEN_WITHDRAW, $pendingWithdraw->getToken())) ||
            ($pendingWithdraw instanceof PendingWithdraw &&
            !$this->isGranted(DisabledServicesVoter::COIN_WITHDRAW))
        ) {
            return $this->createWalletRedirection(
                'danger',
                $this->translator->trans('wallet.withdrawing_no_possible')
            );
        }

        $isBlocked = $pendingWithdraw instanceof PendingWithdraw
            ? $pendingWithdraw->getUser()->isBlocked()
            : ($pendingWithdraw instanceof PendingTokenWithdraw
                ? $pendingWithdraw->getToken()->isBlocked()
                : false
            );

        if ($isBlocked) {
            return $this->createWalletRedirection(
                'danger',
                $this->translator->trans('wallet.account_token_blocked')
            );
        }

        if ($pendingWithdraw instanceof PendingWithdraw) {
            if (!$this->isGranted(DisabledServicesVoter::COIN_WITHDRAW, $pendingWithdraw->getCrypto())) {
                return $this->createWalletRedirection(
                    'danger',
                    $this->translator->trans('wallet.withdraw_disabled')
                );
            }
        }

        if ($pendingWithdraw instanceof PendingWithdraw &&
            !$this->isGranted('not-disabled', $pendingWithdraw->getCrypto())
        ) {
            return $this->createWalletRedirection(
                'danger',
                $this->translator->trans('wallet.withdraw_disabled')
            );
        }

        $this->denyAccessUnlessGranted('edit', $pendingWithdraw);

        $lockWithdrawalDelayError = $this->withdrawalLocksManager->prepareDelayLocks($user->getId());

        if ($lockWithdrawalDelayError) {
            return $this->createWalletRedirection(
                'danger',
                $lockWithdrawalDelayError
            );
        }

        if (!$this->withdrawalLocksManager->acquireLockBalance($user->getId())) {
            return $this->createWalletRedirection(
                'danger',
                $this->translator->trans('wallet.withdrawing_no_possible')
            );
        }

        try {
            $wallet->withdrawCommit($pendingWithdraw);
        } catch (Throwable $exception) {
            return $this->createWalletRedirection(
                'danger',
                $this->translator->trans('wallet.withdraw_something_wrong')
            );
        }

        $this->withdrawLogger->info("Confirm withdrawal for {$pendingWithdraw->getSymbol()}.", [
            'address' => $pendingWithdraw->getAddress()->getAddress(),
            'amount' => $pendingWithdraw->getAmount()->getAmount()->getAmount(),
            'email' => $user->getEmail(),
        ]);

        $this->withdrawalLocksManager->releaseLockBalance();

        return $this->createWalletRedirection(
            'success',
            $this->translator->trans('wallet.transaction_confirmed')
        );
    }

    private function createWalletRedirection(string $type, string $msg): RedirectResponse
    {
        $this->addFlash($type, $msg);

        return $this->redirectToRoute('wallet');
    }
}
