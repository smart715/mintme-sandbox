<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\NotFoundPairException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\BaseQuote;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CoinController extends Controller
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    /** @var MarketStatusManagerInterface */
    private $marketStatusManager;

    /** @var DisabledServicesConfig */
    private $disabledServicesConfig;

    public function __construct(
        NormalizerInterface $normalizer,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        RebrandingConverterInterface $rebrandingConverter,
        MarketStatusManagerInterface $marketStatusManager,
        DisabledServicesConfig $disabledServicesConfig
    ) {
        parent::__construct($normalizer);

        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->marketStatusManager = $marketStatusManager;
        $this->disabledServicesConfig = $disabledServicesConfig;
    }

    /** @Route("/coin/{quote}/{base}", name="coin", defaults={"quote"="mintme"}, options={"expose"=true,"2fa_progress"=false}) */
    public function pair(string $base, string $quote): Response
    {
        $convertedOldUrl = $this->convertOldUrl($base, $quote);

        if ($convertedOldUrl) {
            $base = $convertedOldUrl['base'];
            $quote = $convertedOldUrl['quote'];
        }

        $base = $this->rebrandingConverter->reverseConvert($base);
        $quote = $this->rebrandingConverter->reverseConvert($quote);

        $baseCrypto = $this->cryptoManager->findBySymbol($base);
        $quoteCrypto = $this->cryptoManager->findBySymbol($quote);

        if (null === $baseCrypto || null === $quoteCrypto) {
            throw new NotFoundPairException();
        }

        $market =  $this->marketFactory->create($baseCrypto, $quoteCrypto);

        if (!$this->marketStatusManager->isValid($market, true)) {
            throw new NotFoundPairException();
        }

        if ($convertedOldUrl) {
            return $this->redirectToRoute('coin', [
                'base' => $convertedOldUrl['base'],
                'quote' => $convertedOldUrl['quote'],
            ]);
        }

        /** @var  User|null $user */
        $user = $this->getUser();

        $market = BaseQuote::reverseMarket($market);

        return $this->render('pages/pair.html.twig', [
            'market' => $this->normalize($market),
            'isOwner' => false,
            'showTrade' => true,
            'showDonation' => false,
            'hash' => $user ? $user->getHash() : '',
            'precision' => $quoteCrypto->getShowSubunit(),
            'isTokenPage' => false,
            'tab' => 'trade',
            'disabledServicesConfig' => $this->normalize($this->disabledServicesConfig),
            'showCreatedModal' => false,
        ]);
    }

    private function convertOldUrl(string $base, string $quote): ?array
    {
        $upperCaseBase = mb_strtoupper($base);
        $upperCaseQuote = mb_strtoupper($quote);

        // if reversed base/quote order and web instead of mintme
        if (Symbols::WEB === $upperCaseBase) {
            return [
                'base' => $upperCaseQuote,
                'quote' => $this->rebrandingConverter->convert($upperCaseBase),
            ];
        }

        // if right base/quote order and web instead of mintme
        if (Symbols::WEB === $upperCaseQuote) {
            return [
                'base' => $upperCaseBase,
                'quote' => $this->rebrandingConverter->convert($upperCaseQuote),
            ];
        }

        // if reversed base/quote order but no web instead of mintme
        if (Symbols::MINTME === $upperCaseBase) {
            return [
                'base' => $upperCaseQuote,
                'quote' => $upperCaseBase,
            ];
        }

        return null;
    }
}
