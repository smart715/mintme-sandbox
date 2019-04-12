<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\PendingWithdraw;
use App\Mailer\Mailer;
use App\Manager\CryptoManagerInterface;
use App\Repository\PendingWithdrawRepository;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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
            $this->addFlash(
                'danger',
                'There are no transactions attached to this hashcode'
            );

            return $this->redirectToRoute('wallet');
        }

        $entityManager->remove($pendingWithdraw);
        $entityManager->flush();

        try {
            $wallet->withdraw(
                $this->getUser(),
                $wallet->getDepositCredential(
                    $pendingWithdraw->getUser(),
                    $pendingWithdraw->getCrypto()
                ),
                $pendingWithdraw->getAmount(),
                $pendingWithdraw->getCrypto()
            );
        } catch (Throwable $exception) {
            $this->addFlash(
                'danger',
                'Something went wrong during withdrawal. Contact us or try again later!'
            );

            return $this->redirectToRoute('wallet');
        }

        $this->addFlash(
            'success',
            'Your transaction has been successfully sent.'
        );

        return $this->redirectToRoute('wallet');
    }
}
