<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Token\Token;
use App\Exception\NotFoundPairException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Trade\TraderInterface;
use App\Form\TokenCreateType;
use App\Manager\CryptoManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\TokenNameConverter;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Verify\WebsiteVerifierInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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

    public function __construct(
        EntityManagerInterface $em,
        ProfileManagerInterface $profileManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketManager,
        TraderInterface $trader,
        NormalizerInterface $normalizer
    ) {
        $this->em = $em;
        $this->profileManager = $profileManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->marketManager = $marketManager;
        $this->trader = $trader;

        parent::__construct($normalizer);
    }

    /**
     * @Route("/{name}/{tab}",
     *     name="token_show",
     *     defaults={"tab" = "trade"},
     *     methods={"GET"},
     *     requirements={"tab" = "trade|intro"},
     *     options={"expose"=true}
     * )
     */
    public function show(
        string $name,
        ?string $tab,
        TokenNameConverterInterface $tokenNameConverter
    ): Response {

        $dashedName = TokenNameConverter::parse($name);
        if ($dashedName != $name) {
            return $this->redirectToOwnToken($tab);
        }

        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw new NotFoundPairException();
        }

        $webCrypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);
        $market = $webCrypto
            ? $this->marketManager->create($webCrypto, $token)
            : null;

        return $this->render('pages/pair.html.twig', [
            'token' => $token,
            'currency' => Token::WEB_SYMBOL,
            'stats' => $this->normalize($token->getLockIn()),
            'hash' => $this->getUser() ? $this->getUser()->getHash() : '',
            'profile' => $token->getProfile(),
            'isOwner' => $token === $this->tokenManager->getOwnToken(),
            'tab' => $tab,
            'showIntro' => true,
            'market' => $this->normalize($market),
            'tokenHiddenName' => $market ?
                $tokenNameConverter->convert($token) :
                '',
        ]);
    }

    /** @Route(name="token_create") */
    public function create(
        Request $request,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper
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
                    'profileCreated' => $this->isProfileCreated(),
                    'profileCreated' => true,
                ]);
            }

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

                return $this->redirectToOwnToken('intro');
            } catch (\Throwable $exception) {
                $this->em->remove($token);
                $this->em->flush();
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

        $dashedName = TokenNameConverter::parse($token->getName());

        return $this->redirectToRoute('token_show', [
            'name' => $dashedName,
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
