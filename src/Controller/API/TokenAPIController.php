<?php

namespace App\Controller\API;

use App\Form\TokenType;
use App\Manager\TokenManagerInterface;
use App\Verify\WebsiteVerifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

/** @Rest\Route("/api/token") */
class TokenAPIController extends FOSRestController
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(EntityManagerInterface $entityManager, TokenManagerInterface $tokenManager)
    {
        $this->em = $entityManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/{name}", name="token_update")
     * @Rest\RequestParam(name="_csrf_token", allowBlank=false)
     * @Rest\RequestParam(name="name", allowBlank=false)
     */
    public function update(ParamFetcherInterface $request, SerializerInterface $serializer, string $name): View
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

        $form->submit($request->all(), false);

        $csrfToken = $request->get('_csrf_token');

        $formErrors = $form->getErrors(true, false);
        $formErrorsSerialized = $serializer->serialize($formErrors, 'json');

        if (!$form->isValid() || !$this->isCsrfTokenValid('update-token', $csrfToken)) {
            return $this->view($formErrorsSerialized, Response::HTTP_BAD_REQUEST);
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
}
