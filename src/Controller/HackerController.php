<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/hacker")
 * @Security(expression="is_granted('hacker')")
 * @codeCoverageIgnore
 */
class HackerController extends AbstractController
{
    public const BTC_SYMBOL = 'BTC';

    /** @Route("/crypto/{crypto}", name="hacker-add-crypto", options={"expose"=true}) */
    public function addCrypto(
        string $crypto,
        Request $request,
        BalanceHandlerInterface $balanceHandler,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper
    ): RedirectResponse {
        /** @var string $referer */
        $referer = $request->headers->get('referer');
        $crypto = $cryptoManager->findBySymbol($crypto);
        $user = $this->getUser();

        if (!$crypto || !$user) {
            return $this->redirect($referer);
        }

        $amount = self::BTC_SYMBOL === $crypto->getSymbol() ?
            '0.001' : '100';

        $balanceHandler->deposit(
            $this->getUser(),
            Token::getFromCrypto($crypto),
            $moneyWrapper->parse($amount, $crypto->getSymbol())
        );

        return $this->redirect($referer);
    }

    /**
     * @Route(
     *     "/role/{role}", name="hacker-set-role", requirements={"role"="(user|admin)"}, options={"expose"=true}
     *     )
     */
    public function setRole(
        string $role,
        Request $request,
        UserManagerInterface $userManager
    ): RedirectResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        /** @var string $referer */
        $referer = $request->headers->get('referer');

        if (!$user) {
            return $this->redirect($referer);
        }

        $user->setRoles(['user' === $role ? User::ROLE_DEFAULT : 'ROLE_SUPER_ADMIN']);

        $userManager->updateUser($user);

        $request->getSession()->invalidate();

        return $this->redirect($referer);
    }
}
