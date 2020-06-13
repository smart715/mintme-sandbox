<?php declare(strict_types = 1);

namespace App\Controller;

use App\Manager\MainDocumentsManagerInterfaces;
use App\Manager\ReciprocalLinksManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/test",
     *     name="test",
     *     options={"expose"=true, "sitemap" = true, "2fa_progress" = false}
     * )
     */
    public function test(\App\Manager\UserManagerInterface $um): Response
    {
        $a = $um->findUsers();
        dd($a);
    }

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
     *      options={"sitemap" = true, "2fa_progress"=false}
     * )
     */
    public function privacyPolicy(): Response
    {
        return $this->render('pages/privacy_policy.html.twig');
    }

    /**
     * @Route("/terms-of-service",
     *      name="terms_of_service",
     *      options={"sitemap" = true, "2fa_progress"=false}
     * )
     */
    public function termsOfService(): Response
    {
        return $this->render('pages/terms_of_service.html.twig');
    }

    /**
     * @Route("/mintme-press-kit.pdf", name="press_kit",
     *      options={"2fa_progress"=false}
     * )
     */
    public function pressKit(MainDocumentsManagerInterfaces $mainDocs): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $docsPath = $this->getParameter('docs_path');
        $doc = $mainDocs->findDocPathByName('MintMe Press Kit');

        return new BinaryFileResponse($projectDir.'/public'.$docsPath.'/'.$doc);
    }

    /**
     * @Route("/links",
     *      name="links",
     *      options={"sitemap" = false}
     * )
     */
    public function links(ReciprocalLinksManagerInterface $manager): Response
    {
        return $this->render('pages/links.html.twig', [
            'links' => $manager->getAll(),
        ]);
    }
}
