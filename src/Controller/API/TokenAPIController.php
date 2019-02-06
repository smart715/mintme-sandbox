<?php

namespace App\Controller\API;

use App\Entity\Token\LockIn;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Form\TokenType;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Verify\WebsiteVerifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

/**
 * @Rest\Route("/api/tokens")
 * @Security(expression="is_granted('prelaunch')")
 */
class TokenAPIController extends FOSRestController
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var CryptoManagerInterface */
    protected $cryptoManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->em = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/{name}", name="token_update")
     * @Rest\RequestParam(name="_csrf_token", allowBlank=false)
     * @Rest\RequestParam(name="name", nullable=true)
     * @Rest\RequestParam(name="description", nullable=true)
     * @Rest\RequestParam(name="facebookUrl", nullable=true)
     * @Rest\RequestParam(name="youtubeChannelId", nullable=true)
     */
    public function update(ParamFetcherInterface $request, string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        $form = $this->createForm(TokenType::class, $token, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);

        $form->submit(array_filter($request->all(), function ($value) {
            return null !== $value;
        }), false);

        $csrfToken = $request->get('_csrf_token');

        if (!$form->isValid() || !$this->isCsrfTokenValid('update-token', $csrfToken)) {
            return $this->view($form, Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($token);
        $this->em->flush();

        return $this->view($token, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/website-confirmation", name="token_website_confirm")
     * @Rest\RequestParam(name="url", allowBlank=false)
     */
    public function confirmWebsite(
        ParamFetcherInterface $request,
        WebsiteVerifierInterface $websiteVerifier,
        string $name
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (null === $token->getWebsiteConfirmationToken()) {
            return $this->view(null, Response::HTTP_BAD_REQUEST);
        }

        $url = $request->get('url');

        $validator = Validation::createValidator();
        $urlViolations = $validator->validate($url, new Url());

        if (0 < count($urlViolations)) {
            return $this->view([
                'verified' => false,
                'errors' => array_map(static function ($violation) {
                    return $violation->getMessage();
                }, iterator_to_array($urlViolations)),
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        $isVerified = $websiteVerifier->verify($url, $token->getWebsiteConfirmationToken());

        if ($isVerified) {
            $token->setWebsiteUrl($url);
            $this->em->flush();
        }

        return $this->view(['verified' => $isVerified, 'errors' => []], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/lock-in", name="lock_in")
     * @Rest\RequestParam(name="released", allowBlank=false)
     * @Rest\RequestParam(name="releasePeriod", allowBlank=false)
     * @Rest\RequestParam(name="_csrf_token", allowBlank=false)
     */
    public function setTokenReleasePeriod(
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler,
        string $name
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        $lock = $token->getLockIn() ?? new LockIn($token);

        $form = $this->createFormBuilder($lock, [
                'csrf_protection' => false,
                'allow_extra_fields' => true,
            ])
            ->add('releasePeriod')
            ->getForm();

        $form->submit($request->all());

        if (!$form->isValid() || !$this->isCsrfTokenValid('update-token', $request->get('_csrf_token'))) {
            return $this->view($form);
        }

        if (!$lock->getId()) {
            $balance = $balanceHandler->balance($this->getUser(), $token);

            if ($balance->isFailed()) {
                return $this->view('Service unavailable now. Try later', Response::HTTP_BAD_REQUEST);
            }

            $releasedAmount = $balance->getAvailable()->divide(100)->multiply($request->get('released'));
            $lock->setAmountToRelease($balance->getAvailable()->subtract($releasedAmount));
        }

        $this->em->persist($lock);
        $this->em->flush();

        return $this->view($lock);
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/{tokenName}/balance",
     *     name="fetch_balance_token",
     *     options={"expose"=true},
     *     requirements={"tokenName"="[a-zA-Z0-9]+"}
     *     )
     */
    public function fetchBalanceToken(string $tokenName, BalanceHandlerInterface $balanceHandler): View
    {
        $user = $this->getUser();
        $token = $this->tokenManager->findByName($tokenName);

        if (null === $token) {
            return $this->view([
                'error' => 'Can\'t find a valid token',
            ], Response::HTTP_BAD_REQUEST);
        }

        $balance = $balanceHandler->balance($user, $token);

        if ($balance->isFailed()) {
            return $this->view([
                'error' => 'Exchanger connection lost. Try to reload page.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view($this->tokenManager->getRealBalance($token, $balance));
    }

    /**
     * @Rest\View()
     * @Rest\GET("/search", name="api_token_search")
     * @Rest\QueryParam(name="tokenName", allowBlank=false)
     */
    public function tokenSearch(ParamFetcherInterface $request): View
    {
        return $this->view($this->tokenManager->getTokensByPattern(
            $request->get('tokenName')
        ));
    }

    /**
     * @Rest\View()
     * @Rest\GET("/", name="tokens")
     */
    public function getTokens(
        BalanceHandlerInterface $balanceHandler,
        NormalizerInterface $normalizer
    ): View {
        $tokens = $balanceHandler->balances(
            $this->getUser(),
            $this->getUser()->getRelatedTokens()
        );
        $predefinedTokens = $balanceHandler->balances(
            $this->getUser(),
            $this->tokenManager->findAllPredefined()
        );

        return $this->view([
            'common' => $normalizer->normalize($tokens, null, [
                'groups' => ['Default'],
            ]),
            'predefined' => $normalizer->normalize($predefinedTokens, null, [
                'groups' => ['Default'],
            ]),
        ]);
    }
}
