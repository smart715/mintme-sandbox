<?php

namespace App\Controller;

use App\Entity\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Form\TokenCreateType;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Verify\WebsiteVerifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("/token") */
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
     * @Route("/{name}/{tab}",
     *     name="token_show",
     *     defaults={"tab" = "trade"},
     *     methods={"GET"},
     *     requirements={"tab" = "trade|intro"}
     * )
     */
    public function show(string $name): Response
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            return $this->render('pages/token_404.html.twig');
        }

        $isOwner = $token === $this->tokenManager->getOwnToken();

        return $this->render('pages/token.html.twig', [
            'token' => $token,
            'profile' => $token->getProfile(),
            'isOwner' => $isOwner,
        ]);
    }

    /** @Route(name="token_create") */
    public function create(Request $request, BalanceHandlerInterface $balanceHandler): Response
    {
        $balanceHandler->deposit($this->getUser(), $this->tokenManager->getOwnToken(), '1000000');
        if ($this->isTokenCreated()) {
            return $this->redirectToOwnToken();
        }

        $token = new Token();
        $form = $this->createForm(TokenCreateType::class, $token);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid() && $this->isProfileCreated()) {
            $profile = $this->profileManager->getProfile($this->getUser());

            if (null !== $profile) {
                $token->setProfile($profile);
                $this->em->persist($token);
                $this->em->flush();
            }

            $balanceHandler->deposit($this->getUser(), $token, '1000000');

            return $this->redirectToOwnToken(); //FIXME: redirecto to introduction token page
        }

        return $this->render('pages/token_creation.html.twig', [
            'formHeader' => 'Plant your own token',
            'form' => $form->createView(),
            'profileCreated' => $this->isProfileCreated(),
        ]);
    }

    /** @Route("/{name}/website-confirmation", name="token_website_confirmation") */
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

        $fileContent = WebsiteVerifierInterface::PREFIX.': '.$token->getWebsiteConfirmationToken();
        $response = new Response($fileContent);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'mintme.html'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
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
