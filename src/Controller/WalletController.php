<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\PendingWithdrawInterface;
use App\Entity\User;
use App\Logger\UserActionLogger;
use App\Repository\PendingWithdrawRepository;
use App\Security\Config\DisabledBlockchainConfig;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Wallet\WalletInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;

/**
 * @Route("/wallet")
 */
class WalletController extends Controller
{
    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    public function __construct(
        UserActionLogger $userActionLogger,
        NormalizerInterface $normalizer,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->userActionLogger = $userActionLogger;
        $this->rebrandingConverter = $rebrandingConverter;

        parent::__construct($normalizer);
    }

    /**
     * @Route("/{tab}",
     *     name="wallet",
     *     defaults={"tab"="wallet"},
     *     requirements={"tab"="(dw-history|trade-history|active-orders)"},
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
        ?string $tab
    ): Response {
        $depositMore = $request->get('depositMore') ?? '';

        /** @var  User $user*/
        $user = $this->getUser();

        return $this->render('pages/wallet.html.twig', [
            'hash' => $user->getHash(),
            'depositMore' => $this->rebrandingConverter->reverseConvert($depositMore),
            'disabledBlockchain' => $disabledBlockchainConfig->getDisabledCryptoSymbols(),
            'disabledServicesConfig' => $this->normalize($disabledServicesConfig),
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
        if (!$this->isGranted('withdraw')) {
            return $this->createWalletRedirection(
                'danger',
                'Withdrawing is not possible. Please try again later'
            );
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
                'There are no transactions attached to this hashcode'
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
                'Account or token was blocked. Withdrawing is not possible'
            );
        }

        if ($pendingWithdraw instanceof PendingWithdraw &&
            !$this->isGranted('not-disabled', $pendingWithdraw->getCrypto())
        ) {
            return $this->createWalletRedirection(
                'danger',
                'Withdraw for this crypto was disabled. Please try again later'
            );
        }

        $this->denyAccessUnlessGranted('edit', $pendingWithdraw);

        try {
            $wallet->withdrawCommit($pendingWithdraw);
        } catch (Throwable $exception) {
            return $this->createWalletRedirection(
                'danger',
                'Something went wrong during withdrawal. Contact us or try again later!'
            );
        }

        $this->userActionLogger->info("Confirm withdrawal for {$pendingWithdraw->getSymbol()}.", [
            'address' => $pendingWithdraw->getAddress()->getAddress(),
            'amount' => $pendingWithdraw->getAmount()->getAmount()->getAmount(),
        ]);

        return $this->createWalletRedirection(
            'success',
            'Your transaction has been successfully confirmed and queued to be sent.'
        );
    }

    private function createWalletRedirection(string $type, string $msg): RedirectResponse
    {
        $this->addFlash($type, $msg);

        return $this->redirectToRoute('wallet');
    }
}
