<?php declare(strict_types = 1);

namespace App\Controller;

use App\Form\RegistrationType;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/",
     *     name="homepage",
     *     options={"expose"=true, "sitemap" = true, "2fa_progress" = false}
     * )
     */
    public function index(): Response
    {
        return $this->render('pages/index.html.twig');
    }

    /**
     * @Route("/sign-up", name="sign_up")
     */
    public function signUpLanding(): Response
    {
        return $this->render('pages/sign_up_landing.html.twig', [
            'form' => $this->createForm(RegistrationType::class)->createView(),
        ]);
    }

    /**
     * @Rest\Route("/manifest.json")
     */
    public function manifest(): Response
    {
        return $this->render('manifest.json.twig', [], new JsonResponse());
    }

    /**
     * @Route("/error500", name="error500")
     */
    public function error500(): Response
    {
        throw new \Exception('Exception to test 500 error page in production');
    }

    /**
     * @Route("/privacy-policy",
     *      name="privacy_policy",
     *      options={"sitemap" = true}
     * )
     */
    public function privacyPolicy(): Response
    {
        return $this->render('pages/privacy_policy.html.twig');
    }

    /**
     * @Route("/terms-of-service",
     *      name="terms_of_service",
     *      options={"sitemap" = true}
     * )
     */
    public function termsOfService(): Response
    {
        return $this->render('pages/terms_of_service.html.twig');
    }

    /**
     * @Route("/links",
     *      name="links",
     *      options={"sitemap" = false}
     * )
     */
    public function links(): Response
    {
        return $this->render('pages/links.html.twig');
    }
}
