<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2\User;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandler;
use App\Exchange\Balance\Model\BalanceResult;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Converter\TokenNameConverter;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route(path="/dev/api/v2/auth/user/wallet")
 */
class WalletController extends AbstractFOSRestController
{
    private RebrandingConverterInterface $rebrandingConverter;
    private TokenNameConverter $tokenNameConverter;
    private CryptoManagerInterface $cryptoManager;

    public function __construct(
        RebrandingConverterInterface $rebrandingConverter,
        TokenNameConverter $tokenNameConverter,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->rebrandingConverter = $rebrandingConverter;
        $this->tokenNameConverter = $tokenNameConverter;
        $this->cryptoManager = $cryptoManager;
    }

    /**
     * List users wallet deposit addresses.
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get("/addresses")
     * @SWG\Response(
     *     response="200",
     *     description="Returns wallet deposit addresses related to user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref="#/definitions/Address")
     *     )
     * )
     * @SWG\Response(response="400", description="Bad request")
     * @SWG\Tag(name="User Wallet")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getDepositAddresses(WalletInterface $depositCommunicator): Response
    {
        return $this->forward(
            'App\Controller\Dev\API\V1\User\WalletController::getDepositAddresses',
            [
                'depositCommunicator' => $depositCommunicator,
            ]
        );
    }

    /**
     * List users wallet balances.
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get("/balances")
     * @SWG\Response(
     *     response="200",
     *     description="Returns wallet balances related to user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref="#/definitions/BalanceResult")
     *     )
     * )
     * @SWG\Response(response="400", description="Bad request")
     * @SWG\Tag(name="User Wallet")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getBalances(BalanceHandler $balanceHandler): array
    {
        /** @var User $user */
        $user = $this->getUser();

        $tradebles = array_merge($this->cryptoManager->findAll(), $user->getTokens());

        $balances = $balanceHandler->balances(
            $user,
            $tradebles
        )->getAll();

        return $this->rebrandBalancesKeys($balances, $tradebles);
    }

    /**
     * List deposit/withdraw history.
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get("/history")
     * @Rest\QueryParam(
     *     name="offset",
     *     requirements=@Assert\Range(min="0"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\QueryParam(
     *     name="limit",
     *     requirements=@Assert\Range(min="1", max="101"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>=0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-101]")
     * @SWG\Response(
     *     response="200",
     *     description="Returns wallet deposit/withdraw history related to user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref="#/definitions/Transaction")
     *     )
     * )
     * @SWG\Response(response="400", description="Bad request")
     * @SWG\Tag(name="User Wallet")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getDepositWithdrawHistory(ParamFetcherInterface $request): Response
    {
        return $this->forward(
            'App\Controller\Dev\API\V1\User\WalletController::getDepositWithdrawHistory',
            [
                'request' => $request,
            ],
            [
                'offset' => $request->get('offset'),
                'limit' => $request->get('limit'),
            ]
        );
    }

    /**
     * Withdraw to specific address
     *
     * @Rest\View()
     * @Rest\Post("/withdraw")
     * @SWG\Response(response="404", description="Currency not found")
     * @Rest\RequestParam(name="currency", allowBlank=false)
     * @SWG\Tag(name="User Wallet")
     * @Rest\RequestParam(
     *     name="amount",
     *     allowBlank=false
     * )
     * @SWG\Response(response="400", description="Bad request")
     * @Rest\RequestParam(
     *     name="address",
     *     allowBlank=false,
     *     requirements="^[a-zA-Z0-9]+$"
     * )
     * @SWG\Response(response="201", description="Returns success message")
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      description="JSON Payload",
     *      required=true,
     *      format="application/json",
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="currency", type="string", example="MINTME", description="currency to withdraw"),
     *          @SWG\Property(property="amount", type="string", example="12.33", description="Amount to withdraw"),
     *          @SWG\Property(property="address", type="string", example="0x0..0", description="address to withdraw to"),
     *      )
     * ),
     */
    public function withdraw(
        ParamFetcherInterface $request,
        MoneyWrapperInterface $moneyWrapper,
        MailerInterface $mailer
    ): Response {
        return $this->forward(
            'App\Controller\Dev\API\V1\User\WalletController::withdraw',
            [
                'request' => $request,
                'moneyWrapper' => $moneyWrapper,
                'mailer' => $mailer,
            ],
            [
                'currency' => $request->get('currency'),
                'amount' => $request->get('amount'),
                'address' => $request->get('address'),
            ]
        );
    }

    /**
     * @param BalanceResult[] $balances
     * @param TradebleInterface[] $tradebles
     * @return array
     */
    private function rebrandBalancesKeys(array $balances, array $tradebles): array
    {
        $tokenSymbolMap = [];

        foreach ($tradebles as $tradeble) {
            if ($tradeble instanceof Token) {
                $tokenSymbolMap[$this->tokenNameConverter->convert($tradeble)] = $tradeble->getSymbol();
            }
        }

        $rebrandedBalancesKeys = [];

        foreach ($balances as $key => $balance) {
            if (isset($tokenSymbolMap[$key])) {
                $key = $tokenSymbolMap[$key];
            }

            $rebrandedBalancesKeys[$this->rebrandingConverter->convert((string)$key)] = $balance;
        }

        return $rebrandedBalancesKeys;
    }
}
