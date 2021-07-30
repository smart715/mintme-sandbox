<?php declare(strict_types = 1);

namespace App\Controller;

use App\Manager\ActivityManagerInterface;
use App\Manager\MainDocumentsManagerInterfaces;
use App\Manager\ReciprocalLinksManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultController extends Controller
{
    private const ACTIVITIES_AMOUNT = 30;

    /**
     * @Route("/",
     *     name="homepage",
     *     options={"expose"=true, "sitemap" = true, "2fa_progress" = false}
     * )
     */
    public function index(ActivityManagerInterface $activityManager): Response
    {
        $activities = $activityManager->getLast(self::ACTIVITIES_AMOUNT);

        return $this->render('pages/index.html.twig', [
            'activities' => $this->normalize($activities),
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
     * @Rest\Route("/translations.js", name="translations-ui")
     */
    public function getTranslations(
        Request $request,
        TranslatorInterface $translator,
        CacheInterface $cache
    ): Response {
        $filepath = $this->getParameter('ui_trans_keys_filepath');
        $locale = $request->getLocale();

        // Disabling caching in debug mode/while developing
        $beta = $this->getParameter('kernel.debug') ?
            INF :
            null;

        $content = $cache->get(
            "{$locale}_translations.js",
            function (ItemInterface $item) use ($filepath, $translator) {
                $item->expiresAfter(3600);

                $keys = file_exists($filepath) ?
                    json_decode(file_get_contents($filepath) ?: '[]'):
                    [];

                $parsedKeys = [];

                foreach ($keys as $key) {
                    $parsedKeys[$key] = $translator->trans($key);
                }

                return 'window.translations=' . json_encode($parsedKeys) . ';';
            },
            $beta
        );

        $response = new Response($content, Response::HTTP_OK);

        $response->headers->set('Content-Type', 'text/javascript');

        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        $response->headers->set('Cache-Control', 'public, max-age=3600, immutable');

        return $response;
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
     * @Route("/mintme-aml-policy.pdf", name="aml_policy",
     *      options={"2fa_progress"=false}
     * )
     */
    public function amlPolicy(MainDocumentsManagerInterfaces $mainDocs): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $docsPath = $this->getParameter('docs_path');
        $doc = $mainDocs->findDocPathByName('AML Policy');

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
