<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Blacklist;
use App\Repository\BlacklistRepository;
use Doctrine\ORM\EntityManagerInterface;

class BlacklistManager implements BlacklistManagerInterface
{
    private const TOKEN_NAME_APPEND = [
        'token',
        'coin',
        '-',
    ];

    /** @var BlacklistRepository */
    private $repository;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        /** @var BlacklistRepository $repository */
        $repository = $this->em->getRepository(Blacklist::class);

        $this->repository = $repository;
    }

    public function isBlacklistedAirdropDomain(string $url, bool $sensitive = false): bool
    {
        $domain = parse_url($url, PHP_URL_HOST);

        return $domain
            ? $this->repository->matchValue($domain, Blacklist::AIRDROP_DOMAIN, $sensitive)
            : true;
    }

    public function isBlackListedEmail(string $email, bool $sensitive = false): bool
    {
        $domain = substr($email, strrpos($email, '@') + 1);

        return $this->repository->matchValue($domain, Blacklist::EMAIL, $sensitive);
    }

    public function isBlackListedToken(string $token, bool $sensitive = false): bool
    {
        $token = trim($token);

        $matches = [];
        preg_match("/(\w+)[-\s]+(\w+)/", $token, $matches);
        array_shift($matches);

        $blacklistedNames = array_merge(
            $this->getList(Blacklist::TOKEN),
            $this->getList(Blacklist::CRYPTO_NAME),
            $this->getList(Blacklist::CRYPTO_SYMBOL)
        );

        $firstMatch = false;
        $secondMatch = false;

        foreach ($blacklistedNames as $blacklistedName) {
            $value = $blacklistedName->getValue();
            $type = $blacklistedName->getType();

            if (Blacklist::CRYPTO_SYMBOL === $type) {
                return $this->repository->matchValue($token, Blacklist::CRYPTO_SYMBOL, $sensitive);
            }

            if ($this->nameMatches($token, $value)) {
                return true;
            }

            if (isset($matches[0]) && $this->nameMatches($matches[0], $value)) {
                if ($secondMatch) {
                    return true;
                }

                $firstMatch = true;
            }

            if (isset($matches[1]) && $this->nameMatches($matches[1], $value)) {
                if ($firstMatch) {
                    return true;
                }

                $secondMatch = true;
            }
        }

        return false;
    }

    public function add(string $value, string $type, bool $flush = true): void
    {
        $this->em->persist(new Blacklist(utf8_encode($value), $type));

        if ($flush) {
            $this->em->flush();
        }
    }

    public function bulkAdd(array $values, string $type, int $batchSize = 1000): void
    {
        $index = 0;

        foreach ($values as $value) {
            $index++;
            $this->em->persist(new Blacklist(utf8_encode($value), $type));

            if ($index >= $batchSize) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();
        $this->em->clear();
    }

    public function bulkDelete(string $type, bool $flush = true): void
    {
        $this->repository->bulkDelete($type);

        if ($flush) {
            $this->em->flush();
        }
    }

    /** {@inheritDoc} */
    public function getList(?string $type = null): array
    {
        if (!$type) {
            return $this->repository->findAll();
        }

        return $this->repository->findBy([
            'type' => $type,
        ]);
    }

    private function nameMatches(string $name, string $val): bool
    {
        return (bool)preg_match('/^' . preg_quote($val, '/') . '('. implode('|', self::TOKEN_NAME_APPEND) . ')*$/', $name);
    }
}
