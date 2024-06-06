<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\Blacklist\Blacklist;
use App\Manager\BlacklistManager;
use App\Services\TranslatorService\TranslatorInterface;
use Snipe\BanBuilder\CensorWords;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NoBadWordsValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;
    private BlacklistManager $blacklistManager;
    private CensorWords $censor;
    private array $languages;
    private bool $fullWords;

    public function __construct(
        TranslatorInterface $translator,
        BlacklistManager $blacklistManager,
        CensorWords $censor,
        array $languages,
        bool $fullWords
    ) {
        $this->translator = $translator;
        $this->blacklistManager = $blacklistManager;
        $this->censor = $censor;
        $this->languages = $languages;
        $this->fullWords = $fullWords;
    }

    /**
     * {@inheritDoc}
     *
     * @param NoBadWords $constraint
     * @throws \Exception
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        $value = $this->censor($value);

        if (0 === count($value["matched"])) {
            return;
        }

        $firstBadWord = $this->getFirstBadWord($value);
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ message }}', $this->translator->trans("bad_word.found", [
                "%firstBadWord%" => $firstBadWord,
            ]))
            ->addViolation();
    }

    public function setUpCensor(): void
    {
        $this->censor->setDictionary($this->languages);

        $this->censor->badwords = $this->removeAllWordsLessThan4Characters($this->censor->badwords);

        $blacklistedWords = $this->fetchBlacklistedWords();
        $whitelistedWords = $this->fetchWhitelistedWords();

        $this->censor->addFromArray($blacklistedWords);
        $this->censor->addWhiteList($whitelistedWords);
    }

    public function getCensorChecks(): array
    {
        return $this->fetchCensorChecks();
    }

    public function getWhitelistedWords(): array
    {
        return $this->fetchWhitelistedWords();
    }

    private function getFirstBadWord(array $badWords): string
    {
        return $badWords["matched"][0];
    }

    private function censor(string $value): array
    {
        $this->setUpCensor();

        // escape weird package behaviour with whitelisted capitalized words
        $value = strtolower($value);

        return $this->censor->censorString($value, $this->fullWords);
    }

    private function removeAllWordsLessThan4Characters(array $words): array
    {
        return array_filter($words, function ($word) {
            return strlen($word) >= 4;
        });
    }

    private function fetchBlacklistedWords(): array
    {
        return $this->blacklistManager->getValues(Blacklist::BLACKLISTED_WORDS);
    }

    private function fetchWhitelistedWords(): array
    {
        return $this->blacklistManager->getValues(Blacklist::WHITELISTED_WORDS);
    }

    private function fetchCensorChecks(): array
    {
        $this->setUpCensor();
        $this->forceGenerateCensorChecks();

        $reflection = new \ReflectionProperty($this->censor, 'censorChecks');
        $reflection->setAccessible(true);
        $censorChecks = $reflection->getValue($this->censor);
        $reflection->setAccessible(false);

        return $censorChecks;
    }

    private function forceGenerateCensorChecks(): void
    {
        $reflection = new \ReflectionMethod($this->censor, 'generateCensorChecks');
        $reflection->setAccessible(true);
        $reflection->invoke($this->censor);
        $reflection->setAccessible(false);
    }
}
