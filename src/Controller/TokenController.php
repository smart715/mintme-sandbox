<?php

namespace App\Controller;

use App\Entity\Token;
use App\Form\TokenCreateType;
use App\Form\TokenType;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Verify\WebsiteVerifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

class TokenController extends AbstractController
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var ProfileManagerInterface */
    protected $profileManager;

    /** @var TokenManagerInterface */
    protected $tokenManager;


    public function __construct(
        EntityManagerInterface $em,
        ProfileManagerInterface $profileManager,
        TokenManagerInterface $tokenManager
    ) {
        $this->em = $em;
        $this->profileManager = $profileManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     * @Route("/token/{name}/{tab}",
     *     name="token_show",
     *     defaults={"tab" = "trade"},
     *     methods={"GET"},
     *     requirements={"tab" = "trade|intro"}
     * )
     */
    public function show(Request $request, string $name, string $tab): Response
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            return $this->render('pages/token_404.html.twig');
        }

        $isOwner = $token === $this->tokenManager->getOwnToken();

        return $this->render('pages/token.html.twig', [
            'token' => $token,
            'profile' => $this->profileManager->findByToken($token),
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * @Route("/token", name="token_create", methods={"GET", "POST"})
     */
    public function create(Request $request): Response
    {
        if ($this->isTokenCreated()) {
            return $this->redirectToOwnToken();
        }

        $token = new Token();
        $form = $this->createForm(TokenCreateType::class, $token);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid() && $this->isProfileCreated()) {
            $profile = $this->profileManager->getProfile($this->getUser());
            $profile->setToken($token);
            //TODO: add 1 million tokens
            $this->em->persist($profile);
            $this->em->flush();

            return $this->redirectToOwnToken(); //FIXME: redirecto to introduction token page
        }

        return $this->render('pages/token_creation.html.twig', [
            'formHeader' => 'Plant your own token',
            'form' => $form->createView(),
            'profileCreated' => $this->isProfileCreated(),
        ]);
    }

    /**
     * @Route("/token/{name}", name="token_update", methods={"PATCH"})
     */
    public function update(Request $request, SerializerInterface $serializer, string $name): Response
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

        $form->submit($request->request->all(), false);

        $csrfToken = $request->request->get('_csrf_token');

        $formErrors = $form->getErrors(true, false);
        $formErrorsSerialized = $serializer->serialize($formErrors, 'json');

        if (!$form->isValid() || !$this->isCsrfTokenValid('update-token', $csrfToken)) {
            return new Response($formErrorsSerialized, Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/token/{name}/website-confirmation", name="token_website_confirmation", methods={"GET"})
     */
    public function getWebsiteConfirmationFile(Request $request, string $name): Response
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

        $fileContent = WebsiteVerifierInterface::PREFIX.': '.$token->getWebsiteConfirmationToken();
        $response = new Response($fileContent);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'mintme.html'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/token/{name}/website-confirmation", name="token_website_confirm", methods={"POST"})
     */
    public function confirmWebsite(
        Request $request,
        WebsiteVerifierInterface $websiteVerifier,
        string $name
    ): Response {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (null === $token->getWebsiteConfirmationToken()) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        $url = $request->request->get('url');

        $validator = Validation::createValidator();
        $urlViolations = $validator->validate($url, new Url());

        if (0 < count($urlViolations)) {
            return new JsonResponse([
                'verified' => false,
                'errors' => array_map(static function ($violation) {
                    return $violation->getMessage();
                }, iterator_to_array($urlViolations)),
            ]);
        }

        $isVerified = $websiteVerifier->verify($url, $token->getWebsiteConfirmationToken());

        if ($isVerified) {
            $token->setWebsiteUrl($url);
            $this->em->flush();
        }

        return new JsonResponse(['verified' => $isVerified, 'errors' => []]);
    }

    private function redirectToOwnToken(): RedirectResponse
    {
        $token = $this->tokenManager->getOwnToken();

        if (null === $token) {
            //FIXME: return "token not exist" template instead
            throw $this->createNotFoundException('User doesn\'t have a token created.');
        }

        return $this->redirectToRoute('token_show', [
            'name' => $token->getName(),
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
