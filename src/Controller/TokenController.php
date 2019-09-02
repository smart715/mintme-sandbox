<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Token\Token;
use App\Exception\NotFoundTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Trade\TraderInterface;
use App\Form\TokenCreateType;
use App\Logger\UserActionLogger;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
 * @Security(expression="is_granted('prelaunch')")
 */
class TokenController extends Controller
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var ProfileManagerInterface */
    protected $profileManager;

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
        UserActionLogger $userActionLogger
    ) {
        $this->em = $em;
        $this->profileManager = $profileManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->marketManager = $marketManager;
        $this->trader = $trader;
        $this->userActionLogger = $userActionLogger;

        parent::__construct($normalizer);
    }

    /**
     * @Route("/{name}/{tab}",
     *     name="token_show",
     *     defaults={"tab" = "trade"},
     *     methods={"GET"},
     *     requirements={"tab" = "trade|intro"},
     *     options={"expose"=true,"2fa_progress"=false}
     * )
     */
    public function show(
        string $name,
        ?string $tab,
        TokenNameConverterInterface $tokenNameConverter
    ): Response {

        $dashedName = (new StringConverter(new DashStringStrategy()))->convert($name);

        if ($dashedName != $name) {
            return $this->redirectToRoute('token_show', ['name' => $dashedName]);
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

        return $this->render('pages/pair.html.twig', [
            'token' => $token,
            'currency' => Token::WEB_SYMBOL,
            'hash' => $this->getUser() ? $this->getUser()->getHash() : '',
            'profile' => $token->getProfile(),
            'isOwner' => $token === $this->tokenManager->getOwnToken(),
            'tab' => $tab,
            'showIntro' => true,
            'market' => $this->normalize($market),
            'tokenHiddenName' => $market ?
                $tokenNameConverter->convert($token) :
                '',
            'precision' => $this->getParameter('token_precision'),
        ]);
    }

    /** @Route(name="token_create") */
    public function create(
        Request $request,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        MarketStatusManagerInterface $marketStatusManager
    ): Response {
        if ($this->isTokenCreated()) {
            return $this->redirectToOwnToken('trade');
        }

        $token = new Token();
        $form = $this->createForm(TokenCreateType::class, $token);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->isProfileCreated()) {
            $profile = $this->profileManager->getProfile($this->getUser());

            if ($this->tokenManager->isExisted($token)) {
                $form->addError(new FormError('Token name is already exists.'));

                return $this->render('pages/token_creation.html.twig', [
                    'formHeader' => 'Create your own token',
                    'form' => $form->createView(),
                    'profileCreated' => true,
                ]);
            }

            $this->em->beginTransaction();

            if (null !== $profile) {
                $token->setProfile($profile);
                $this->em->persist($token);
                $this->em->flush();
            }

            try {
                $balanceHandler->deposit(
                    $this->getUser(),
                    $token,
                    $moneyWrapper->parse(
                        (string)$this->getParameter('token_quantity'),
                        MoneyWrapper::TOK_SYMBOL
                    )
                );
                $market = $this->marketManager->createUserRelated($this->getUser());

                $marketStatusManager->createMarketStatus($market);

                $this->em->commit();
                $this->userActionLogger->info('Create a token', ['name' => $token->getName(), 'id' => $token->getId()]);

                return $this->redirectToOwnToken('intro');
            } catch (Throwable $exception) {
                $this->em->rollback();
                $this->userActionLogger->error('Got an error, when registering a token',
                    ['error' => $exception, 'detailed error' => $exception->getMessage()]);
                $this->addFlash('danger', 'Exchanger connection lost. Try again.');
            }
        }

        return $this->render('pages/token_creation.html.twig', [
            'formHeader' => 'Create your own token',
            'form' => $form->createView(),
            'profileCreated' => $this->isProfileCreated(),
        ]);
    }

    /**
     * @Route("/{name}/website-confirmation", name="token_website_confirmation")
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

    private function isProfileCreated(): bool
    {
        return null !== $this->profileManager->getProfile($this->getUser());
    }
}
