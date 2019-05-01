<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\PendingWithdraw;
use App\Repository\PendingWithdrawRepository;
use App\Wallet\WalletInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * @Route("/wallet")
 * @Security(expression="is_granted('prelaunch')")
 */
class WalletController extends Controller
{
    /**
     * @Route(name="wallet")
     */
    public function wallet(): Response
    {
        return $this->render('pages/wallet.html.twig', [
            'hash' => $this->getUser()->getHash(),
            'precision' => $this->getParameter('token_precision'),
        ]);
    }

    /** @Route("/withdraw/{hash}", name="withdraw-confirm", options={"expose"=true}) */
    public function withdrawConfirm(string $hash, WalletInterface $wallet): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        /** @var PendingWithdrawRepository $withdrawRepo */
        $withdrawRepo = $entityManager->getRepository(PendingWithdraw::class);

        /** @var PendingWithdraw|null */
        $pendingWithdraw = $withdrawRepo->getWithdrawByHash($hash);

        if (!$pendingWithdraw) {
            return $this->createWalletRedirection(
                'danger',
                'There are no transactions attached to this hashcode'
            );
        }

        $entityManager->remove($pendingWithdraw);
        $entityManager->flush();

        try {
            $wallet->withdraw(
                $pendingWithdraw->getUser(),
                $pendingWithdraw->getAddress(),
                $pendingWithdraw->getAmount(),
                $pendingWithdraw->getCrypto()
            );
        } catch (Throwable $exception) {
            return $this->createWalletRedirection(
                'danger',
                'Something went wrong during withdrawal. Contact us or try again later!'
            );
        }

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
