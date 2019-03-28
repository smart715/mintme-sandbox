<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\TokenNameConverterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BalanceViewFactory implements BalanceViewFactoryInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var TokenNameConverterInterface */
    private $tokenNameConverter;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TokenManagerInterface $tokenManager,
        TokenNameConverterInterface $tokenNameConverter
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->tokenManager = $tokenManager;
        $this->tokenNameConverter = $tokenNameConverter;
    }

    /** {@inheritdoc} */
    public function create(BalanceResultContainer $container): array
    {
        $result = [];

        foreach ($container as $key => $balanceResult) {
            $token = $this->tokenManager->findByName($key) ?? $this->tokenManager->findByHiddenName($key);

            if (!$token) {
                continue;
            }

            $name = $token->getName();
            $fee = null;

            if ($token->getCrypto()) {
                $fee = $token->getCrypto()->getFee();
                $name = $token->getCrypto()->getName();
            }

            $securityToken = $this->tokenStorage->getToken();
            $result[$token->getName()] = new BalanceView(
                $this->tokenNameConverter->convert($token),
                $this->tokenManager->getRealBalance(
                    $token,
                    $balanceResult
                )->getAvailable(),
                $token->getLockIn() ? $token->getLockIn()->getFrozenAmount() : null,
                $name,
                $fee,
                $securityToken && $token->getProfile() && $securityToken->getUser() === $token->getProfile()->getUser()
            );
        }

        return $result;
    }
}
