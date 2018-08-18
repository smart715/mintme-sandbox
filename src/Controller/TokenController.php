<?php

namespace App\Controller;

use App\Entity\Token;
use App\Form\TokenCreateType;
use App\Form\TokenType;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
            return $this->render('default/token_not_exist.html.twig');
        }

        $isOwner = $token == $this->tokenManager->getOwnToken();

        return $this->render('default/token.html.twig', [
            'token' => $token,
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * @Route("/token", name="token_create", methods={"GET", "POST"})
     */
    public function create(Request $request): Response
    {
        if ($this->isTokenCreated())
            return $this->redirectToOwnToken();

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

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
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
