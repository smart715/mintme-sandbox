<?php declare(strict_types = 1);

namespace App\Manager;

use App\Config\CryptoConfig;
use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Manager\Model\TradableNetworkModel;
use App\Repository\CryptoRepository;
use App\Repository\CryptoVotingRepository;
use App\Services\TranslatorService\TranslatorInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

class CryptoManager implements CryptoManagerInterface
{

    private CryptoRepository $repository;
    private CryptoVotingRepository $cryptoVotingRepository;
    private HideFeaturesConfig $hideFeaturesConfig;
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;
    private CryptoConfig $cryptoConfig;

    public function __construct(
        CryptoRepository $repository,
        HideFeaturesConfig $hideFeaturesConfig,
        CryptoVotingRepository $cryptoVotingRepository,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        CryptoConfig $cryptoConfig
    ) {
        $this->repository = $repository;
        $this->cryptoVotingRepository = $cryptoVotingRepository;
        $this->hideFeaturesConfig = $hideFeaturesConfig;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->cryptoConfig = $cryptoConfig;
    }

    public function findBySymbol(string $symbol, bool $ignoreEnabled = false): ?Crypto
    {
        $symbol = strtoupper($symbol);

        return $ignoreEnabled || $this->hideFeaturesConfig->isCryptoEnabled($symbol)
            ? $this->repository->getBySymbol($symbol)
            : null;
    }

    /** {@inheritdoc} */
    public function findAll(): array
    {
        $all = $this->repository->findAll();

        return array_values(array_filter($all, function ($crypto) {
            return $this->hideFeaturesConfig->isCryptoEnabled($crypto->getSymbol());
        }));
    }

    public function findAllAssets(): array
    {
        $all = $this->repository->findAll();

        return array_values(array_filter($all, function ($crypto) {
            return $crypto->isAsset() && $this->hideFeaturesConfig->isCryptoEnabled($crypto->getSymbol());
        }));
    }

    public function findSymbolAndSubunitArr(): array
    {
        return $this->repository->createQueryBuilder('c')
            ->select('c.symbol, c.subunit')
            ->getQuery()
            ->getArrayResult();
    }

    public function findAllIndexed(string $index, bool $array = false, bool $onlyEnabled = true): array
    {
        $query = $this->repository->createQueryBuilder('c', "c.{$index}");

        if ($onlyEnabled) {
            $query->where($query->expr()->in('c.symbol', $this->hideFeaturesConfig->getAllEnabledCryptos()));
        }

        $query->orderBy('c.id', Criteria::ASC);

        $query = $query->getQuery();

        return $array
            ? $query->getArrayResult()
            : $query->getResult();
    }

    public function getVotingByCryptoId(int $cryptoId, int $offset, int $limit): array
    {
        return $this->cryptoVotingRepository->getVotingByCryptoId($cryptoId, $offset, $limit) ?? [];
    }

    public function getVotingCountAll(): int
    {
        return $this->cryptoVotingRepository->getVotingCountAll();
    }

    public function create(
        string $name,
        string $symbol,
        int $subunit,
        int $nativeSubunit,
        int $showSubunit,
        bool $tradable,
        bool $exchangeble,
        bool $isToken = false,
        ?string $fee = null,
        ?Crypto $nativeCoin = null
    ): Crypto {
        if ($isToken && (bool)$fee) {
            throw new \Exception('Fee must be set in WrappedCryptoToken if Crypto is a token');
        }

        $crypto = new Crypto(
            $name,
            $symbol,
            $subunit,
            $nativeSubunit,
            $showSubunit,
            $tradable,
            $exchangeble,
            $isToken,
            $fee,
            $nativeCoin
        );

        $this->entityManager->persist($crypto);
        $this->entityManager->flush();

        return $crypto;
    }

    public function update(Crypto $crypto): Crypto
    {
        $this->entityManager->persist($crypto);
        $this->entityManager->flush();

        return $crypto;
    }

    /** {@inheritdoc} */
    public function getCryptoNetworks(Crypto $crypto, ?bool $includingDisabled = false): array
    {
        $networks = [];
        $defaultNetwork = $this->cryptoConfig->getCryptoDefaultNetwork($crypto->getSymbol());

        if (!$crypto->isToken()) {
            $networks[] = new TradableNetworkModel(
                $this->getNetworkName($crypto->getSymbol()),
                $crypto,
                $crypto->getFee(),
                $crypto->getShowSubunit(),
                null,
                $defaultNetwork === $crypto->getSymbol(),
            );
        }

        $cryptosMap = $this->findAllIndexed('symbol');

        foreach ($crypto->getWrappedCryptoTokens($includingDisabled) as $wct) {
            if (!isset($cryptosMap[$wct->getFee()->getCurrency()->getCode()])) {
                continue;
            }

            $networks[] = new TradableNetworkModel(
                $this->getNetworkName($wct->getCryptoDeploy()->getSymbol()),
                $wct->getCryptoDeploy(),
                $wct->getFee(),
                $cryptosMap[$wct->getFee()->getCurrency()->getCode()]->getShowSubunit(),
                $wct->getAddress(),
                $defaultNetwork === $wct->getCryptoDeploy()->getSymbol(),
            );
        }

        return $networks;
    }

    public function getNetworkName(string $symbol): string
    {
        $translationKey = "dynamic.blockchain_{$symbol}_name";
        $translated = $this->translator->trans($translationKey, [], null, 'en');

        return $translated === $translationKey
            ? $symbol
            : $translated;
    }
}
