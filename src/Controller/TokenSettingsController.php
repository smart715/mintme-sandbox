<?php declare(strict_types = 1);

namespace App\Controller;

use App\Communications\DiscordOAuthClientInterface;
use App\Config\HideFeaturesConfig;
use App\Entity\PromotionHistory;
use App\Entity\Rewards\Reward;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\NotFoundRewardException;
use App\Exception\NotFoundTokenException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\RewardManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Security\Config\DisabledBlockchainConfig;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use FOS\RestBundle\Controller\Annotations\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TokenSettingsController extends Controller
{
    private const DISCORD_SUB_TAB = 'discord_rewards';
    public const SHOW_DISCORD_TAB_SESSION_NAME = 'token_settings_show_discord_tab';

    protected TokenManagerInterface $tokenManager;
    protected CryptoManagerInterface $cryptoManager;
    private MarketFactoryInterface $marketFactory;
    private RebrandingConverterInterface $rebrandingConverter;
    private DiscordOAuthClientInterface $discordOAuthClient;
    private RewardManagerInterface $rewardManager;
    private DisabledServicesConfig $disabledServicesConfig;
    private DisabledBlockchainConfig $disabledBlockchainConfig;
    private SessionInterface $session;
    private HideFeaturesConfig $hideFeaturesConfig;

    public function __construct(
        TokenManagerInterface $tokenManager,
        NormalizerInterface $normalizer,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        RebrandingConverterInterface $rebrandingConverter,
        DiscordOAuthClientInterface $discordOAuthClient,
        RewardManagerInterface $rewardManager,
        DisabledServicesConfig $disabledServicesConfig,
        DisabledBlockchainConfig $disabledBlockchainConfig,
        SessionInterface $session,
        HideFeaturesConfig $hideFeaturesConfig
    ) {
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->discordOAuthClient = $discordOAuthClient;
        $this->rewardManager = $rewardManager;
        $this->disabledServicesConfig = $disabledServicesConfig;
        $this->disabledBlockchainConfig = $disabledBlockchainConfig;
        $this->session = $session;
        $this->hideFeaturesConfig = $hideFeaturesConfig;

        parent::__construct($normalizer);
    }

    /**
     * @Route(
     *     path="/token-settings/{tokenName}/{tab}/{sub}/{modal}/{slug}",
     *     name="token_settings",
     *     methods={"GET"},
     *     requirements={
     *         "tab" = "general|promotion|advanced|deploy|markets",
     *         "modal" = "reward-summary|reward-finalize",
     *         "sub" = "bounty|token_shop|airdrop|discord_rewards|token_promotion|signup_bonus",
     *     },
     *     options={"expose"=true})
     * @throws NotFoundTokenException
     */
    public function show(
        Request $request,
        ?string $tokenName = null,
        string $tab = 'general',
        ?string $sub = null,
        ?string $modal = null,
        ?string $slug = null
    ): Response {
        /** @var  User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('homepage');
        }

        $profile = $user->getProfile();

        if (!$profile->hasTokens()) {
            return $this->redirectToRoute('token_create');
        }

        if ((preg_match('/(reward-summary|reward-finalize)/', $request->getPathInfo()) && 'promotion' !== $tab)
            || ('markets' === $tab && !$this->hideFeaturesConfig->isNewMarketsEnabled())
            || (in_array($sub, ['bounty', 'token_shop']) && !$this->hideFeaturesConfig->isRewardsEnabled())
        ) {
            return $this->redirectToRoute('token_settings', ['tokenName' => $tokenName]);
        }

        $token = null === $tokenName
            ? $this->tokenManager->getOwnMintmeToken()
            : $this->tokenManager->findByName($tokenName);

        if (null === $token) {
            throw new NotFoundTokenException();
        }

        $this->denyAccessUnlessGranted('edit', $token);

        $markets = $this->marketFactory->createTokenMarkets($token);

        $rewards = $this->rewardManager->getUnfinishedRewardsByToken($token);
        $reward = $this->getReward($modal, $slug);

        return $this->render('pages/token_settings.html.twig', [
            'token' => $token,
            'tokenDescription' => $token->getDescription() ?? '',
            'deploys' => $this->normalize($token->getDeploys()),
            'activeTab' => $tab,
            'activeSubTab' => $sub,
            'hash' => $user->getHash(),
            'precision' => $this->getParameter('token_precision'),
            'currentMarket' => $this->normalize($this->getCurrentMarket($token, $markets)),
            'markets' => $this->normalize($markets),
            'socialUrls' => $this->getTokenSocialUrls($token),
            'discordAuthUrl' => $this->getDiscordAuthUrl($token),
            'showRewardSummaryModal' => $this->getShowRewardSummaryModal($modal, $slug),
            'rewards' => $this->normalize($rewards[Reward::TYPE_REWARD]),
            'bounties' => $this->normalize($rewards[Reward::TYPE_BOUNTY]),
            'disabledServicesConfig' => $this->normalize($this->disabledServicesConfig),
            'disabledBlockchain' => $this->disabledBlockchainConfig->getDisabledCryptoSymbols(),
            'tokenDeleteSoldLimit' => $this->getParameter('token_delete_sold_limit'),
            'tokenProposalMinAmount' => (float)$token->getTokenProposalMinAmount(),
            'dmMinAmount' => (float)$token->getDmMinAmount(),
            'commentMinAmount' => (float)$token->getCommentMinAmount(),
            'reward' => $this->normalize($reward, ['API']),
            'subTab' => $this->getSubTab($reward, $modal),
            'tokens' => $this->normalize($profile->getTokens()),
            'tokensCount' => $profile->getTokensCount(),
            'enabledCryptos' => $this->normalize($this->cryptoManager->findAll()),
        ]);
    }

    private function getReward(?string $modal, ?string $slug): ?Reward
    {
        $isRewardModal = TokenController::REWARD_SUMMARY_MODAL === $modal ||
            TokenController::REWARD_FINALIZE_MODAL === $modal;

        $reward = null;

        if ($slug && $isRewardModal) {
            $reward = $this->rewardManager->getBySlug($slug);

            if (!$reward || $reward->isFinishedReward()) {
                throw new NotFoundRewardException();
            }

            if (TokenController::REWARD_SUMMARY_MODAL === $modal) {
                $this->denyAccessUnlessGranted('edit', $reward);
            }
        }

        return $reward;
    }

    private function getShowRewardSummaryModal(?string $modal, ?string $slug): bool
    {
        $reward = $this->getReward($modal, $slug);

        $extraData = $slug && $reward ? ['reward' => $this->normalize($reward, ['API'])] : [];

        return TokenController::REWARD_SUMMARY_MODAL === $modal && array_key_exists('reward', $extraData);
    }

    private function getDiscordAuthUrl(Token $token): string
    {
        $this->session->set('locale_lang', $this->session->get('_locale'));

        $discordCallbackUrl = $this->generateUrl(
            'discord_callback_bot',
            ['_locale' => 'en'],
            UrlGenerator::ABSOLUTE_URL
        );

        return $this->discordOAuthClient->generateAuthUrl(
            'bot applications.commands',
            $discordCallbackUrl,
            DiscordOAuthClientInterface::BOT_PERMISSIONS_ADMINISTRATOR,
            (string)$token->getId()
        );
    }

    private function getCurrentMarket(Token $token, array $markets): Market
    {

        $exchangeCrypto = $this->cryptoManager->findBySymbol(
            $this->rebrandingConverter->reverseConvert(Symbols::WEB)
        );

        if (!$exchangeCrypto || !$token->containsExchangeCrypto($exchangeCrypto)) {
            throw new NotFoundTokenException();
        }

        return $markets[$exchangeCrypto->getSymbol()];
    }

    private function getTokenSocialUrls(Token $token): array
    {
        return [
            'facebookUrl' => $token->getFacebookUrl(),
            'youtubeChannelId' => $token->getYoutubeChannelId(),
            'telegramUrl' => $token->getTelegramUrl(),
            'discordUrl' => $token->getDiscordUrl(),
            'websiteUrl' => $token->getWebsiteUrl(),
            'twitterUrl' => $token->getTwitterUrl(),
        ];
    }

    private function getSubTab(?Reward $reward, ?string $modal): ?string
    {
        if ($this->session->get(self::SHOW_DISCORD_TAB_SESSION_NAME)) {
            $this->session->remove(self::SHOW_DISCORD_TAB_SESSION_NAME);

            return self::DISCORD_SUB_TAB;
        }

        if (!$reward || !$modal) {
            return null;
        }

        return $reward->isBountyType()
            ? PromotionHistory::BOUNTY
            : PromotionHistory::TOKEN_SHOP;
    }
}
