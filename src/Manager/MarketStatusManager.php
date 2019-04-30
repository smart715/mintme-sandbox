<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Repository\MarketStatusRepository;
use App\Utils\Converter\MarketNameConverterInterface;
use Doctrine\ORM\EntityManagerInterface;

class MarketStatusManager implements MarketStatusManagerInterface
{
    /** @var MarketStatusRepository */
    protected $repository;

    /** @var MarketNameConverterInterface */
    protected $marketNameConverter;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        MarketNameConverterInterface $marketNameConverter,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        MarketHandlerInterface $marketHandler
    ) {
        $this->repository = $em->getRepository(MarketStatus::class);
        $this->marketNameConverter = $marketNameConverter;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketFactory = $marketFactory;
        $this->marketHandler = $marketHandler;
        $this->em = $em;
    }

    public function getMarketsInfo(): array
    {
        $marketsInfo = [];

        /** @var MarketStatus[] $info */
        $info = $this->repository->findAll();

        foreach ($info as $marketInfo) {
            $tokenName = $marketInfo->getTokenName();
            $quote = $this->cryptoManager->findBySymbol($tokenName) ?? $this->tokenManager->findByName($tokenName);

            if (!$quote) {
                continue;
            }

            $market = $this->marketFactory->create($marketInfo->getCrypto(), $quote);

            if (!$market) {
                continue;
            }

            $marketsInfo[$this->marketNameConverter->convert($market)] = $marketInfo;
        }

        return $marketsInfo;
    }

    public function createMarketStatus(array $markets): void
    {
        /** @var Market $market */
        foreach ($markets as $market) {
            $marketInfo = $this->marketHandler->getMarketInfo($market);
            $crypto = $this->cryptoManager->findBySymbol($market->getBase()->getSymbol());
            $quouteCrypto = $this->cryptoManager->findBySymbol($market->getQuote()->getSymbol());

            $token = !$quouteCrypto
                 ? $this->tokenManager->findByName($market->getQuote()->getName())
                 : Token::getFromCrypto($quouteCrypto)->setCrypto($quouteCrypto);

            if (!$crypto || !$token) {
                new \InvalidArgumentException();
            }

            $this->em->persist(new MarketStatus($crypto, $marketInfo));
            $this->em->flush();
        }
    }
}
