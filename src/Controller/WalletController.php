<?php

namespace App\Controller;

use App\Deposit\DepositGatewayCommunicatorInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandler;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcher;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/** @Route("/wallet") */
class WalletController extends AbstractController
{
    /**
     * @Route(name="wallet")
     */
    public function wallet(
        BalanceHandler $balanceHandler,
        MarketFetcher $marketFetcher,
        CryptoManagerInterface $cryptoManager,
        DepositGatewayCommunicatorInterface $depositCommunicator,
        MarketManagerInterface $marketManager,
        TokenManagerInterface $tokenManager,
        NormalizerInterface $normalizer,
        MoneyWrapperInterface $moneyWrapper
    ): Response {
        $tokens = $balanceHandler->balances(
            $this->getUser(),
            $this->getUser()->getRelatedTokens()
        );
        
        $webCrypto = $cryptoManager->findBySymbol(Token::WEB_SYMBOL);
        $token = $this->getUser()->getProfile()->getToken();
        
        $market = $webCrypto && $token
            ? $marketManager->getMarket($webCrypto, $token)
            : null;
           
        $executedHistory = $market
            ? $marketFetcher->getUserExecutedHistory($this->getUser()->getId(), $market, $moneyWrapper)
            : null;
            
        $predefinedTokens = $balanceHandler->balances(
            $this->getUser(),
            $tokenManager->findAllPredefined()
        );

        try {
            $depositAddresses = $depositCommunicator->getDepositCredentials(
                $this->getUser()->getId(),
                $tokenManager->findAllPredefined()
            )->toArray();
        } catch (\Throwable $e) {
            $depositAddresses = $depositCommunicator->getUnavailableCredentials(
                $tokenManager->findAllPredefined()
            )->toArray();
        }
        
        $ownToken = $tokenManager->getOwnToken();
        $markets = $ownToken ? $this->createMarkets($ownToken, $cryptoManager->findAll()) : [];
        return $this->render('pages/wallet.html.twig', [
            'markets' => $markets,
            'hash' => $this->getUser()->getHash(),
            'executedHistory' => $normalizer->normalize($executedHistory),
            'tokens' => $normalizer->normalize($tokens, null, [
                'groups' => [ 'Default' ],
            ]),
            'predefinedTokens' => $normalizer->normalize($predefinedTokens, null, [
                'groups' => [ 'Default' ],
            ]),
            'depositAddresses' => $depositAddresses,
        ]);
    }

    /**
     * @param Crypto[] $cryptos
     * @return Market[]
     */
    private function createMarkets(Token $token, array $cryptos): array
    {
        return array_map(function (Crypto $crypto) use ($token) {
            return null !== $token
                ? (new Market($crypto, $token))->getHiddenName()
                : null;
        }, $cryptos);
    }
}
