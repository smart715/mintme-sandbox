<?php declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Traits\CheckTokenNameBlacklistTrait;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\NotFoundTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Trade\TraderInterface;
use App\Form\TokenCreateType;
use App\Logger\UserActionLogger;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\BlacklistManager;
use App\Manager\BlacklistManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\String\DashStringStrategy;
use App\Utils\Converter\String\StringConverter;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Utils\Verify\WebsiteVerifierInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;

/**
 * @Route("/token")
 */
class TokenController extends Controller
{

    use CheckTokenNameBlacklistTrait;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var ProfileManagerInterface */
    protected $profileManager;

    /** @var BlacklistManagerInterface */
    protected $blacklistManager;

    /** @var TokenManagerInterface */
    protected $tokenManager;

    /** @var CryptoManagerInterface */
    protected $cryptoManager;

    /** @var MarketFactoryInterface */
    protected $marketManager;

    /** @var TraderInterface */
    protected $trader;

    /** @var UserActionLogger  */
    private $userActionLogger;


    public function __construct(
        EntityManagerInterface $em,
        ProfileManagerInterface $profileManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketManager,
        TraderInterface $trader,
        NormalizerInterface $normalizer,
        UserActionLogger $userActionLogger,
        BlacklistManager $blacklistManager
    ) {
        $this->em = $em;
        $this->profileManager = $profileManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->marketManager = $marketManager;
        $this->trader = $trader;
        $this->userActionLogger = $userActionLogger;
        $this->blacklistManager = $blacklistManager;

        parent::__construct($normalizer);
    }

    /**
     * @Route("/{name}/{tab}",
     *     name="token_show",
     *     defaults={"tab" = "intro"},
     *     methods={"GET"},
     *     requirements={"tab" = "trade|intro|posts"},
     *     options={"expose"=true,"2fa_progress"=false}
     * )
     */
    public function show(
        Request $request,
        string $name,
        ?string $tab,
        TokenNameConverterInterface $tokenNameConverter,
        AirdropCampaignManagerInterface $airdropCampaignManager
    ): Response {
        if (preg_match('/(intro)/', $request->getPathInfo())) {
            return $this->redirectToRoute('token_show', ['name' => $name]);
        }

        $dashedName = (new StringConverter(new DashStringStrategy()))->convert($name);

        if ($dashedName != $name) {
            return $this->redirectToRoute('token_show', ['name' => $dashedName]);
        }

        //rebranding
        if (Token::MINTME_SYMBOL === mb_strtoupper($name)) {
            $name = Token::WEB_SYMBOL;
        }

        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw new NotFoundTokenException();
        }

        if ($this->tokenManager->isPredefined($token)) {
            return $this->redirectToRoute(
                'coin',
                [
                    'base'=> (Token::WEB_SYMBOL == $token->getName() ? Token::BTC_SYMBOL : $token->getName()),
                    'quote'=> Token::WEB_SYMBOL,
                ]
            );
        }

        $webCrypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);
        $market = $webCrypto
            ? $this->marketManager->create($webCrypto, $token)
            : null;
        $tokenDescription = preg_replace(
            '/\[\/?(?:b|i|u|s|ul|ol|li|p|s|url|img|h1|h2|h3|h4|h5|h6)*?.*?\]/',
            '\2',
            $token->getDescription() ?? ''
        );
        $metaDescription = str_replace("\n", " ", $tokenDescription ?? '');

        /** @var  User|null $user */
        $user = $this->getUser();

        return $this->render('pages/pair.html.twig', [
            'showSuccessAlert' => $request->get('alert', false),
            'token' => $token,
            'tokenDescription' => substr($metaDescription, 0, 200),
            'currency' => Token::WEB_SYMBOL,
            'hash' => $user ? $user->getHash() : '',
            'profile' => $token->getProfile(),
            'isOwner' => $token === $this->tokenManager->getOwnToken(),
            'isTokenCreated' => $this->isTokenCreated(),
            'tab' => $tab,
            'showTrade' => true,
            'market' => $this->normalize($market),
            'tokenHiddenName' => $market ?
                $tokenNameConverter->convert($token) :
                '',
            'precision' => $this->getParameter('token_precision'),
            'isTokenPage' => true,
            'showAirdropCampaign' => $token->getActiveAirdrop() ? true : false,
            'userAlreadyClaimed' => $airdropCampaignManager
                ->checkIfUserClaimed($user, $token),
            'posts' => $this->normalize($token->getPosts()),
        ]);
    }

    /**
     * @Route(name="token_create", options={"expose"=true})
     * @throws ApiBadRequestException
     */
    public function create(
        Request $request,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        MarketStatusManagerInterface $marketStatusManager
    ): Response {
        if ($this->isTokenCreated()) {
            return $this->redirectToOwnToken('intro');
        }

        $token = new Token();
        $form = $this->createForm(TokenCreateType::class, $token);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->all() as $childForm) {
                /** @var FormError[] $fieldErrors */
                $fieldErrors = $form->get($childForm->getName())->getErrors();

                if (count($fieldErrors) > 0) {
                    throw new ApiBadRequestException($fieldErrors[0]->getMessage());
                }
            }

            throw new ApiBadRequestException('Invalid argument');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->checkTokenNameBlacklist($token->getName())) {
                return $this->json(
                    ['blacklisted' => true, 'message' => 'Forbidden token name, please try another'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $this->em->beginTransaction();

            /** @var User $user */
            $user = $this->getUser();
            $token->setProfile(
                $this->profileManager->getProfile($this->getUser()) ?? new Profile($user)
            );
            $this->em->persist($token);
            $this->em->flush();

            try {
                /** @var  \App\Entity\User $user*/
                $user = $this->getUser();

                $balanceHandler->deposit(
                    $user,
                    $token,
                    $moneyWrapper->parse(
                        (string)$this->getParameter('token_quantity'),
                        MoneyWrapper::TOK_SYMBOL
                    )
                );
                $market = $this->marketManager->createUserRelated($user);

                $marketStatusManager->createMarketStatus($market);

                $this->em->commit();
                $this->userActionLogger->info('Create a token', ['name' => $token->getName(), 'id' => $token->getId()]);

                return $this->json("success", Response::HTTP_ACCEPTED);
            } catch (Throwable $exception) {
                if (false !== strpos($exception->getMessage(), 'cURL')) {
                    $this->addFlash('danger', 'Exchanger connection lost. Try again');

                    $this->userActionLogger->error(
                        'Got an error, when registering a token: ',
                        ['message' => $exception->getMessage()]
                    );
                } else {
                    $this->em->rollback();
                    $this->addFlash('danger', 'Error creating token. Try again');

                    $this->userActionLogger->error(
                        'Got an error, when registering a token',
                        ['message' => $exception->getMessage()]
                    );
                }
            }
        }

        return $this->render('pages/token_creation.html.twig', [
            'formHeader' => 'Create your own token',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{name}/website-confirmation", name="token_website_confirmation", options={"expose"=true})
     */
    public function getWebsiteConfirmationFile(string $name): Response
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (null === $token->getWebsiteConfirmationToken()) {
            $token->setWebsiteConfirmationToken(Uuid::uuid1()->toString());
            $this->em->flush();
        }

        $fileContent = WebsiteVerifierInterface::PREFIX . ': ' . $token->getWebsiteConfirmationToken();
        $response = new Response($fileContent);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'mintme.html'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function redirectToOwnToken(?string $showtab = 'trade'): RedirectResponse
    {
        $token = $this->tokenManager->getOwnToken();

        if (null === $token) {
            throw $this->createNotFoundException('User doesn\'t have a token created.');
        }

        $tokenDashed = (new StringConverter(new DashStringStrategy()))->convert($token->getName());

        return $this->redirectToRoute('token_show', [
            'name' => $tokenDashed,
            'tab' => $showtab,
        ]);
    }

    private function isTokenCreated(): bool
    {
        return null !== $this->tokenManager->getOwnToken();
    }
}
