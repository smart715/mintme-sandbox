<?php

namespace App\Controller;

use App\Entity\Token;
use App\Form\TokenFormType;
use App\Manager\ProfileManagerInterface;
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
     * @Route("/token/{name}/{tab}", name="token_show",
     * defaults={"tab" = "trade"}, requirements={"tab" = "trade|intro"})
     * @Method({"GET"})
     */
    public function show(Request $request, string $name): Response
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            return $this->render('default/token_not_exist.html.twig');
        }

        return $this->render('default/token.html.twig', [
            'token' => $token,
        ]);
    }

    /**
     * @Route("/token", name="token_create")
     */
    public function create(Request $request): Response
    {
        if ($this->isTokenCreated())
            return $this->redirectToOwnToken();

        $token = new Token();
        $form = $this->createForm(TokenFormType::class, $token);
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
