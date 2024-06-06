<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Hashtag;
use App\Exception\ApiBadRequestException;
use App\Repository\HashtagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class HashtagManager implements HashtagManagerInterface
{
    public const POPULAR_HASHTAGS_CACHE_KEY = 'popular_hashtags';
    // matching hashtag with any lang char and numbers up to 100 chars (count it until valid chars)
    public const HASHTAG_REGEXP = '/#([\p{L}\p{N}_]{1,100})(?=[^_#\p{L}\p{N}]|$)/u';
    public const MAX_HASHTAGS_PER_POST = 50;

    private EntityManagerInterface $em;
    private HashtagRepository $hashtagRepository;
    private CacheInterface $cache;
    private TranslatorInterface $translator;
    private int $cacheMaxAge;
    private int $hashtagsPickInterval;

    public function __construct(
        EntityManagerInterface $em,
        HashtagRepository $hashtagRepository,
        CacheInterface $cache,
        TranslatorInterface $translator,
        int $cacheMaxAge,
        int $hashtagsPickInterval
    ) {
        $this->em = $em;
        $this->hashtagRepository = $hashtagRepository;
        $this->cache = $cache;
        $this->translator = $translator;
        $this->cacheMaxAge = $cacheMaxAge;
        $this->hashtagsPickInterval = $hashtagsPickInterval;
    }

    public function findHashtagsByKeyword(string $query): array
    {
        return $this->hashtagRepository->findPopularHashtagsByKeyword($query);
    }

    /** @inheritDoc */
    public function findOrCreate(string $content): array
    {
        if (!preg_match_all(self::HASHTAG_REGEXP, $content, $matches)) {
            return [];
        }

        $hashtags = $matches[1];
        $normalizedHashtags = array_map([$this, 'normalizeHashtagValue'], $hashtags);

        $uniqueHashtags = [];
        $existingHashtagsMap = [];

        foreach ($normalizedHashtags as $hashtag) {
            $lowercaseHashtag = strtolower($hashtag);
            
            if (!in_array($lowercaseHashtag, $existingHashtagsMap, true)) {
                $uniqueHashtags[] = $hashtag;
                $existingHashtagsMap[] = $lowercaseHashtag;
            }
        }

        if (count($uniqueHashtags) > self::MAX_HASHTAGS_PER_POST) {
            throw new ApiBadRequestException(
                $this->translator->trans(
                    'api.max_hashtags',
                    ['%limit%' => self::MAX_HASHTAGS_PER_POST]
                )
            );
        }

        $existingHashtags = $this->hashtagRepository->findBy(['value' => $uniqueHashtags]);

        $missingHashtags = array_udiff($uniqueHashtags, array_map(static fn ($hashtag) => $hashtag->getValue(), $existingHashtags), 'strcasecmp');

        foreach ($missingHashtags as $missingHashtagValue) {
            $newHashtag = new Hashtag($this->normalizeHashtagValue($missingHashtagValue));
            $this->em->persist($newHashtag);
            $existingHashtags[] = $newHashtag;
        }

        $this->em->flush();

        return $existingHashtags;
    }

    public function getPopularHashtags(): array
    {
        return $this->cache->get(
            self::POPULAR_HASHTAGS_CACHE_KEY,
            function (ItemInterface $item) {
                $item->expiresAfter($this->cacheMaxAge);

                $fromDate = new \DateTimeImmutable("-$this->hashtagsPickInterval seconds");

                return $this->generatePopularHashtags($fromDate);
            }
        );
    }

    public function normalizeHashtagValue(string $hashtag): string
    {
        $words = preg_split('/[_\s]+/', $hashtag);

        if (!$words) {
            throw new ApiBadRequestException($this->translator->trans('api.something_went_wrong'));
        }

        $pascalCaseWords = array_map('ucfirst', $words);

        return implode('', $pascalCaseWords);
    }

    private function generatePopularHashtags(\DateTimeImmutable $fromDate): array
    {
        return array_reduce($this->hashtagRepository->getPopularHashtags($fromDate), function ($result, $hashtag) {
            $result[$hashtag['value']] = $hashtag['total'];

            return $result;
        }, []);
    }
}
