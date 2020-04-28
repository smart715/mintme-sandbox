<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Money\Money;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/airdrop_campaign")
 */
class AirdropCampaignController extends AbstractFOSRestController
{
    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var AirdropCampaignManagerInterface */
    private $airdropCampaignManager;

    public function __construct(
        TokenManagerInterface $tokenManager,
        AirdropCampaignManagerInterface $airdropCampaignManager
    ) {
        $this->tokenManager = $tokenManager;
        $this->airdropCampaignManager = $airdropCampaignManager;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{tokenName}", name="get_airdrop_campaign", options={"expose"=true})
     */
    public function getAirdropCampaign(string $tokenName): View
    {
        $token = $this->fetchToken($tokenName);

        $data = [
            'airdrop' => $token->getActiveAirdrop(),
            'airdropParams' => $this->getParameter('airdrop_params'),
        ];

        return $this->view($data, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/create", name="create_airdrop_campaign", options={"expose"=true})
     * @Rest\RequestParam(
     *     name="amount",
     *     allowBlank=false,
     *     description="Amount of tokens."
     * )
     * @Rest\RequestParam(
     *     name="participants",
     *     allowBlank=false,
     *     description="Amount of participants."
     * )
     * @Rest\RequestParam(
     *     name="endDate",
     *     allowBlank=true,
     *     nullable=true,
     *     description="Airdrop campaign end date timestamp."
     * )
     */
    public function createAirdropCampaign(
        string $tokenName,
        MoneyWrapperInterface $moneyWrapper,
        BalanceHandlerInterface $balanceHandler,
        Request $request
    ): View {
        $airdropParams = $this->getParameter('airdrop_params');
        $token = $this->fetchToken($tokenName, true);
        $amount = $moneyWrapper->parse((string)$request->get('amount'), MoneyWrapper::TOK_SYMBOL);
        $minAmount = $moneyWrapper->parse((string)$airdropParams['min_tokens_amount'], MoneyWrapper::TOK_SYMBOL);
        $minReward = $moneyWrapper->parse((string)$airdropParams['min_token_reward'], MoneyWrapper::TOK_SYMBOL);
        $balance = $balanceHandler->balance(
            $token->getProfile()->getUser(),
            $token
        )->getAvailable();
        $participants = (int)$request->get('participants');
        $endDateTimestamp = $request->get('endDate');

        $this->checkAirdropAmount($amount, $minAmount, $balance);
        $this->checkAirdropReward($amount, $participants, $minReward);
        $this->checkAirdropParticipants(
            $participants,
            $airdropParams['min_participants_amount'],
            $airdropParams['max_participants_amount']
        );
        $this->checkAirdropEndDate($endDateTimestamp);

        $endDate = $endDateTimestamp
            ? (new \DateTimeImmutable())->setTimestamp($endDateTimestamp)
            : null;

        $airdrop = $this->airdropCampaignManager->createAirdrop(
            $token,
            $amount,
            $participants,
            $endDate
        );

        return $this->view([
            'id' => $airdrop->getId(),
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\View()
     * @Rest\Delete(
     *     "/{id}/delete",
     *     name="delete_airdrop_campaign",
     *     options={"expose"=true},
     *     requirements={"id"="\d+"}
     * )
     */
    public function deleteAirdropCampaign(Airdrop $airdrop): View
    {
        $this->denyAccessUnlessGranted('edit', $airdrop->getToken());
        $this->airdropCampaignManager->deleteAirdrop($airdrop);

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/claim", name="claim_airdrop_campaign", options={"expose"=true})
     */
    public function claimAirdropCampaign(string $tokenName): View
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $token = $this->fetchToken($tokenName, false, true);

        if (!$token->getActiveAirdrop()) {
            throw new ApiBadRequestException('Token does not have active airdrop campaign.');
        }

        $this->airdropCampaignManager->claimAirdropCampaign(
            $user,
            $token
        );

        return $this->view(null, Response::HTTP_ACCEPTED);
    }

    private function checkAirdropAmount(Money $amount, Money $minAmount, Money $balance): void
    {
        if ($amount->lessThan($minAmount) || $amount->greaterThan($balance)) {
            throw new ApiBadRequestException('Invalid amount.');
        }
    }

    private function checkAirdropReward(Money $amount, int $participants, Money $minReward): void
    {
        $reward = $amount->divide($participants);

        if ($reward->lessThan($minReward)) {
            throw new ApiBadRequestException(
                'Invalid reward. Set higher amount of tokens for airdrop or lower amount of participants.'
            );
        }
    }

    private function checkAirdropParticipants(int $participants, int $minParticipants, int $maxParticipants): void
    {
        if ($participants < $minParticipants || $participants > $maxParticipants) {
            throw new ApiBadRequestException('Invalid participants amount.');
        }
    }

    private function checkAirdropEndDate(?int $endDateTimestamp): void
    {
        if ($endDateTimestamp && $endDateTimestamp < time()) {
            throw new ApiBadRequestException('Invalid end date.');
        }
    }

    private function fetchToken(
        string $tokenName,
        bool $checkIfOwner = false,
        bool $checkIfParticipant = false
    ): Token {
        /** @var Token|null $token */
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw $this->createNotFoundException('Token does not exist.');
        }

        if ($checkIfOwner) {
            $this->denyAccessUnlessGranted('edit', $token);
        }

        if ($checkIfParticipant && $token === $this->tokenManager->getOwnToken()) {
            throw new ApiBadRequestException('Sorry, you can\'t participate in your own airdrop.');
        }

        return $token;
    }
}
