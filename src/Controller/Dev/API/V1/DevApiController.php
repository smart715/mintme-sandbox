<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V1;

use App\Entity\Token\Token;
use App\Exception\ApiNotFoundException;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;

abstract class DevApiController extends AbstractFOSRestController
{
    private const DISALLOWED_VALUES = [
        Token::WEB_SYMBOL,
    ];

    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
    }

    protected function checkForDisallowedValues(string $base, ?string $quote = null): void
    {
        if (in_array(mb_strtoupper($base), self::DISALLOWED_VALUES)
            || in_array(mb_strtoupper($quote ?? ''), self::DISALLOWED_VALUES)) {
            if (null === $quote) {
                throw new ApiNotFoundException('Currency not found');
            }

            throw new ApiNotFoundException('Market not found');
        }
    }

    protected function checkForTokenCryptoMarkets(string $base, string $quote): bool
    {
        $baseEntity = $this->cryptoManager->findBySymbol($base) ?? $this->tokenManager->findByName($base);
        $quoteEntity = $this->cryptoManager->findBySymbol($quote) ?? $this->tokenManager->findByName($quote);

        if (!$baseEntity || !$quoteEntity) {
            throw new ApiNotFoundException('Market not found');
        }

        $market = new Market($baseEntity, $quoteEntity);

        return $market->isTokenCryptoMarket();
    }
}
