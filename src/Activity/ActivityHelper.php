<?php declare(strict_types = 1);

namespace App\Activity;

use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Events\TransactionCompletedEventInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Asset\Packages;

class ActivityHelper
{
    private CacheManager $imageCacheManager;
    private Packages $packages;
    private CryptoManagerInterface $cryptoManager;
    private MarketFactoryInterface $marketFactory;
    private MarketStatusManagerInterface $marketStatusManager;
    private MoneyWrapperInterface $moneyWrapper;
    private RebrandingConverterInterface $rebrandingConverter;

    public function __construct(
        CacheManager $imageCacheManager,
        Packages $packages,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        MarketStatusManagerInterface $marketStatusManager,
        MoneyWrapperInterface $moneyWrapper,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->imageCacheManager = $imageCacheManager;
        $this->packages = $packages;
        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
        $this->marketStatusManager = $marketStatusManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->rebrandingConverter = $rebrandingConverter;
    }

    public function rebrand(string $value): string
    {
        return $this->rebrandingConverter->convert($value);
    }

    public function bnbToBsc(string $val): string
    {
        return Symbols::BNB === $val
            ? Symbols::BSC
            : $val;
    }

    public function getCoinAvatarAssetName(string $symbol, bool $isAvatar = false): string
    {
        $symbol = Symbols::MINTME === $symbol
            ? Symbols::WEB
            : $symbol;

        return $isAvatar
            ? "${symbol}_avatar.svg"
            : "${symbol}.svg";
    }

    public function tokenIcon(Token $token): string
    {
        return $this->imageCacheManager->generateUrl($token->getImage()->getUrl(), 'avatar_small');
    }

    public function tradeIcon(string $symbol, ?TradableInterface $token = null): string
    {
        return $token instanceof Token
            ? $this->tokenIcon($token)
            : $this->packages->getUrl('build/images/' . $this->getCoinAvatarAssetName($symbol));
    }

    public function profileIcon(User $user): string
    {
        return $this->imageCacheManager->generateUrl($user->getProfile()->getImage()->getUrl(), 'avatar_small');
    }

    public function rebrandBlockchain(string $blockchain): string
    {
        return $this->rebrand($this->bnbToBsc($blockchain));
    }

    public function truncate(string $value, int $max): string
    {
        $decodedValue = html_entity_decode($value);

        return strlen($decodedValue) > $max
            ? mb_substr($decodedValue, 0, $max) . '...'
            : $decodedValue;
    }

    public function getLastPriceWorthInMintMe(TransactionCompletedEventInterface $event): string
    {
        $amount = $this->moneyWrapper->parse($event->getAmount(), Symbols::TOK);

        $base = $this->cryptoManager->findBySymbol(Symbols::WEB);
        $market = $this->marketFactory->create($base, $event->getTradable());
        $marketStatus = $this->marketStatusManager->getOrCreateMarketStatus($market);

        $lastPrice = $marketStatus->getLastPrice();

        $lastPrice = $this->moneyWrapper->format($lastPrice);

        $price = $this->moneyWrapper->convertByRatio(
            $amount,
            Symbols::WEB,
            $lastPrice
        );

        return $this->moneyWrapper->format($price, false);
    }
}
