<?php

namespace App\Controller;

use App\Entity\Token;
use App\Form\TokenFormType;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TokenController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager
    ) {
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     * @Route("/token/{name}", name="token_view")
     * @Method({"GET"})
     */
    public function tokenView(Request $request, string $name): Response
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('The token "'.$name.'" does not exist.');
        }

        return $this->render('defaults/token_view.html.twig', [
            'token' => $token,
        ]);
    }

    /**
     * @Route("/my_token", name="my_token")
     */
    public function myToken(Request $request): Response
    {
        if ($this->isTokenCreated())
            return $this->redirectToOwnToken();

        $token = new Token();
        $form = $this->createForm(TokenFormType::class, $token);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid() && $this->isProfileCreated()) {
            $profile = $this->getUser()->getProfile();
            $profile->setToken($token);
            $this->entityManager->persist($profile);
            $this->entityManager->flush();

            return $this->redirectToOwnToken();
        }

        return $this->render('defaults/my_token.html.twig', [
            'formHeader' => 'Create token',
            'form' => $form->createView(),
            'profileCreated' => $this->isProfileCreated(),
        ]);
    }

    private function redirectToOwnToken(): RedirectResponse
    {
        $token = $this->tokenManager->getOwnToken($this->getUser());

        if (null === $token) {
            throw $this->createNotFoundException('User doesn\'t have a token created.');
        }

        return $this->redirectToRoute('token_view', [
            'name' => $token->getName(),
        ]);
    }

    private function isTokenCreated(): bool
    {
        return null !== $this->tokenManager->getOwnToken($this->getUser());
    }

    private function isProfileCreated(): bool
    {
        return null !== $this->getUser()->getProfile();
    }
}
