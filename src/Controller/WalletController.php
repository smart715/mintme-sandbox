<?php

namespace App\Controller;

use App\Deposit\DepositGatewayCommunicatorInterface;
use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandler;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\WalletInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/wallet")
 * @Security(expression="is_granted('prelaunch')")
 */
class WalletController extends AbstractController
{
    private const DEPOSIT_WITHDRAW_HISTORY_LIMIT = 20;

    /**
     * @Route(name="wallet")
     */
    public function wallet(
        BalanceHandler $balanceHandler,
        MarketHandlerInterface $marketHandler,
        CryptoManagerInterface $cryptoManager,
        DepositGatewayCommunicatorInterface $depositCommunicator,
        MarketManagerInterface $marketManager,
        TokenManagerInterface $tokenManager,
        NormalizerInterface $normalizer,
        WalletInterface $wallet
    ): Response {

        try {
            $tokens = $balanceHandler->balances(
                $this->getUser(),
                $this->getUser()->getRelatedTokens()
            );
            $predefinedTokens = $balanceHandler->balances(
                $this->getUser(),
                $tokenManager->findAllPredefined()
            );
        } catch (\Throwable $exception) {
            $tokens = false;
            $predefinedTokens = false;
        }

        $webCrypto = $cryptoManager->findBySymbol(Token::WEB_SYMBOL);

        /** @var User $user */
        $user = $this->getUser();

        /** @var Profile|null $profile */
        $profile = $user->getProfile();

        $token = $profile
            ? $profile->getToken()
            : null;

        $market = $webCrypto && $token
            ? $marketManager->getMarket($webCrypto, $token)
            : null;

        $executedHistory = [];
        $orders = [];

        if ($market) {
            try {
                $executedHistory = $marketHandler->getUserExecutedHistory($user, $marketManager->getUserRelatedMarkets($user));
            } catch (\Throwable $exception) {
                $executedHistory = false;
            }
            try {
                $orders = $marketHandler->getPendingOrdersByUser($user, $marketManager->getUserRelatedMarkets($user));
            } catch (\Throwable $exception) {
                $orders = false;
            }
        }

        try {
            $depositWithdrawHistory = $normalizer->normalize(
                $wallet->getWithdrawDepositHistory($this->getUser(), 0, self::DEPOSIT_WITHDRAW_HISTORY_LIMIT)
            );
        } catch (\Throwable $exception) {
            $depositWithdrawHistory = false;
        }

        return $this->render('pages/wallet.html.twig', [
            'orders' => $normalizer->normalize($orders, null, [
                'groups' => [ 'Default' ],
            ]),
            'markets' => $normalizer->normalize($marketManager->getUserRelatedMarkets($this->getUser()), null, [
                'groups' => [ 'Default' ],
            ]),
            'hash' => $this->getUser()->getHash(),
            'executedHistory' => $normalizer->normalize($executedHistory, null, [
                'groups' => [ 'Default' ],
            ]),
            'tokens' => $normalizer->normalize($tokens, null, [
                'groups' => [ 'Default' ],
            ]),
            'predefinedTokens' => $normalizer->normalize($predefinedTokens, null, [
                'groups' => [ 'Default' ],
            ]),
            'depositAddresses' => $depositCommunicator->getDepositCredentials(
                $this->getUser()->getId(),
                $tokenManager->findAllPredefined()
            )->toArray(),
            'depositWithdrawHistory' => $depositWithdrawHistory,
        ]);
    }
}
