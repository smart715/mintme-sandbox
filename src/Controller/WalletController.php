<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\PendingWithdrawInterface;
use App\Entity\User;
use App\Logger\UserActionLogger;
use App\Repository\PendingWithdrawRepository;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Wallet\WalletInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
     * @param Request $request
     * @Route(name="wallet", options={"expose"=true})
     * @return Response
     */
    public function wallet(Request $request): Response
    {
        $depositMore = $request->get('depositMore') ?? '';

        return $this->render('pages/wallet.html.twig', [
            'hash' => $this->getUser()->getHash(),
            'depositMore' => $this->rebrandingConverter->reverseConvert($depositMore),
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
            'Your transaction has been successfully sent.'
        );
    }

    private function createWalletRedirection(string $type, string $msg): RedirectResponse
    {
        $this->addFlash($type, $msg);

        return $this->redirectToRoute('wallet');
    }
}
