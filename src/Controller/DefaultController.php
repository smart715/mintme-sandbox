<?php declare(strict_types = 1);

namespace App\Controller;

use App\Activity\ActivityTypes;
use App\Config\PostsConfig;
use App\Entity\Translation;
use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\ActivityManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MainDocumentsManagerInterfaces;
use App\Manager\MarketStatusManager;
use App\Manager\ReciprocalLinksManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TranslationsManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\BaseQuote;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DefaultController extends Controller
{
    private const ACTIVITIES_AMOUNT = 30;
    private const USERS_TOP_TOKENS_AMOUNT = 10;
    private const GUESTS_TOP_TOKENS_AMOUNT = 5;

    /**
     * @Route("/",
     *     name="homepage",
     *     options={"expose"=true, "sitemap" = true, "2fa_progress" = false}
     * )
     */
    public function index(
        Request $request,
        ActivityManagerInterface $activityManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        PostsConfig $postsConfig,
        MarketFactoryInterface $marketFactory,
        MarketStatusManager $marketStatusManager,
        RebrandingConverterInterface $rebrandingConverter
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        $topTokens = $this->getTopTokens(
            $activityManager,
            $marketStatusManager,
            $rebrandingConverter,
            $user
        );

        if ($user) {
            return $this->renderUserFeedPage(
                $request,
                $activityManager,
                $marketFactory,
                $cryptoManager,
                $tokenManager,
                $postsConfig,
                $topTokens,
            );
        }

        $market = $marketFactory->create(
            $cryptoManager->findBySymbol(Symbols::BTC),
            $cryptoManager->findBySymbol(Symbols::WEB)
        );

        $activities = $activityManager->getLast(self::ACTIVITIES_AMOUNT);

        $tab = $request->query->get('tab');
        $tab = in_array($tab, ['all', 'feed'])
            ? $tab
            : 'all';

        return $this->render('pages/index.html.twig', [
            'postRewardsCollectableDays' => $this->getParameter('post_rewards_collectable_days'),
            'activities' => $this->normalize($activities),
            'enabledCryptos' => $this->normalize($cryptoManager->findAll()),
            'youtube_video_id' => $this->getParameter('homepage_youtube_video_id'),
            'isAuthorizedForReward' => $this->isGranted('collect-reward'),
            'commentTipCost' => $postsConfig->getCommentsTipCost(),
            'commentTipMinAmount' => $postsConfig->getCommentsTipMinAmount(),
            'commentTipMaxAmount' => $postsConfig->getCommentsTipMaxAmount(),
            'precision' => $this->getParameter('token_precision'),
            'market' => $this->normalize($market),
            'hashtag' => $request->query->get('hashtag'),
            'activeTab' => $tab,
            'topTokens' => $this->normalize($topTokens),
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
        $translator->setLocale($locale);

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
    public function privacyPolicy(
        Request $request,
        TranslationsManagerInterface $translationsManagerInterface
    ): Response {
        $locale = $request->getLocale();

        $translations = $translationsManagerInterface->getAllTranslationByLanguage(
            Translation::PP,
            $locale,
            true,
        );

        return $this->render('pages/privacy_policy.html.twig', [
            'translations' => $translations,
        ]);
    }

    /**
     * @Route("/terms-of-service",
     *      name="terms_of_service",
     *      options={"sitemap" = true, "2fa_progress"=false}
     * )
     */
    public function termsOfService(
        Request $request,
        TranslationsManagerInterface $translationsManagerInterface
    ): Response {
        $locale = $request->getLocale();

        $translations = $translationsManagerInterface->getAllTranslationByLanguage(
            Translation::TOS,
            $locale,
            true,
        );

        return $this->render('pages/terms_of_service.html.twig', [
            'translations' => $translations,
        ]);
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

    private function renderUserFeedPage(
        Request $request,
        ActivityManagerInterface $activityManager,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        PostsConfig $postsConfig,
        array $topTokens
    ): Response {
        $market = $marketFactory->create(
            $cryptoManager->findBySymbol(Symbols::WEB),
            $cryptoManager->findBySymbol(Symbols::BTC)
        );
        $market = BaseQuote::reverseMarket($market);

        $activities = $activityManager->getLast(self::ACTIVITIES_AMOUNT);

        $tab = $request->query->get('tab');
        $tab = in_array($tab, ['all', 'feed'])
            ? $tab
            : 'all';

        return $this->render('pages/show_user_feed.html.twig', [
            'postRewardsCollectableDays' => $this->getParameter('post_rewards_collectable_days'),
            'isAuthorizedForReward' => $this->isGranted('collect-reward'),
            'ownDeployedTokens' => $this->normalize($tokenManager->getOwnDeployedTokens(), ['API_BASIC']),
            'commentTipCost' => $postsConfig->getCommentsTipCost(),
            'commentTipMinAmount' => $postsConfig->getCommentsTipMinAmount(),
            'commentTipMaxAmount' => $postsConfig->getCommentsTipMaxAmount(),
            'precision' => $this->getParameter('token_precision'),
            'market' => $this->normalize($market),
            'hashtag' => $request->query->get('hashtag'),
            'tokens' => $this->normalize($tokenManager->getOwnTokens(), ['API_BASIC']),
            'activities' => $this->normalize($activities),
            'activeTab' => $tab,
            'topTokens' => $this->normalize($topTokens),
        ]);
    }

    private function getTopTokens(
        ActivityManagerInterface $activityManager,
        MarketStatusManager $marketStatusManager,
        RebrandingConverterInterface $rebrandingConverter,
        ?User $user
    ): array {
        $topTokens = $activityManager->getLastByTypes(
            [ActivityTypes::TOKEN_TRADED, ActivityTypes::DONATION],
            $user ? self::USERS_TOP_TOKENS_AMOUNT : self::GUESTS_TOP_TOKENS_AMOUNT
        );
        $markets = [];

        foreach ($topTokens as $token) {
            if (null === $token['fullTokenName']) {
                continue;
            }

            $market = $marketStatusManager->findByBaseQuoteNames(
                $rebrandingConverter->reverseConvert($token['symbol']),
                $token['fullTokenName']
            );

            if (null === $market) {
                continue;
            }

            $markets[] = $market;
        }

        return $marketStatusManager->convertMarketStatusKeys($markets);
    }
}
