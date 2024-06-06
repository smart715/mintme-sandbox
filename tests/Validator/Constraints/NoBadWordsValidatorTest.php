<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Blacklist\Blacklist;
use App\Manager\BlacklistManager;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\NoBadWordsValidator;
use Snipe\BanBuilder\CensorWords;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NoBadWordsValidatorTest extends ConstraintValidatorTestCase
{
    private const BLACK_LISTED_WORDS = ['blacklisted_word'];
    private const WHITE_LISTED_WORDS = ['shit'];
    protected function createValidator(): NoBadWordsValidator
    {
        return new NoBadWordsValidator(
            $this->mockTranslator(),
            $this->mockBlacklistManager(),
            new CensorWords(),
            ['en-base', 'en-uk', 'en-us', 'es', 'fr'],
            true
        );
    }

    /** @dataProvider provider */
    public function testValidate(string $value, bool $isValid): void
    {
        $this->validator->validate($value, $this->constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($this->constraint->message)
                ->setParameter('{{ message }}', 'test')
                ->assertRaised();
        }
    }

    public function provider(): array
    {
        return [
            "Empty string" => ['value' => '', 'isValid' => true],
            "No bad words" => ['value' => 'TEST', 'isValid' => true],
            "Bad word between words" => ['value' => 'TESTfuckTEST', 'isValid' => true],
            "Bad word between words to it's left" => ['value' => 'TESTfuck', 'isValid' => true],
            "Bad word between words to it's right" => ['value' => 'fuckTEST', 'isValid' => true],
            "Bad word with additional chars" => ['value' => 'fucking', 'isValid' => true],
            "Whitelisted word" => ['value' => 'shit', 'isValid' => true],
            "Whitelisted word capitalized" => ['value' => 'SHIT', 'isValid' => true],
            "Bad word with less than 4 chars" => ['value' => 'fuk', 'isValid' => true],
            "Bad word with numbers instead of spaces" => ['value' => 'fuck2fuck', 'isValid' => true],
            "Bad word with tricky letters" => ['value' => 'TESTƒuckTEST', 'isValid' => true],
            "Bad word with spaces" => ['value' => 'fuck hello', 'isValid' => false],
            "Bad word" => ['value' => 'fuck', 'isValid' => false],
            "Bad word capitalized" => ['value' => 'FUCK', 'isValid' => false],
            "Bad word with html special characters" => ['value' => 'TEST^&fuck;*TEST', 'isValid' => false],
            "Bad word with symbols instead of spaces" => ['value' => 'prince#albert-fuck', 'isValid' => false],
            "Bad word with 2+ spaces instead of 1 space" => ['value' => 'prince  fuck  piercing', 'isValid' => false],
            "Bad word with hyphens instead of spaces" => ['value' => 'TEST---fuck-TEST', 'isValid' => false],
            "Bad word with non alphabet instead of spaces" => ['value' => 'fuck/*-+waffle', 'isValid' => false],
            "Blacklisted word" => ['value' => 'blacklisted_word', 'isValid' => false],
            "Blacklisted word capitalized" => ['value' => 'BLACKLISTED_WORD', 'isValid' => false],
        ];
    }

    public function testGetCensorChecks(): void
    {
        /** @var NoBadWordsValidator $validator */
        $validator = $this->validator;

        $censorChecks = $validator->getCensorChecks();

        $this->assertIsArray($censorChecks);
        $this->assertContains(
            '/(b|b\.|b\-|8|\|3|ß|Β|β)(i|i\.|i\-|!|\||\]\[|]|1|∫|Ì|Í|Î|Ï|ì|í|î|ï)(t|t\.|t\-|Τ|τ|7)(c|c\.|c\-|Ç|ç|¢|€|<|\(|{|©)(h|h\.|h\-|Η)/i', //phpcs:ignore
            $censorChecks
        );
    }

    public function testGetWhitelistedWords(): void
    {
        /** @var NoBadWordsValidator $validator */
        $validator = $this->validator;

        $this->assertEquals(
            self::WHITE_LISTED_WORDS,
            $validator->getWhitelistedWords()
        );
    }

    private function mockTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')->willReturn('test');

        return $translator;
    }

    private function mockBlacklistManager(): BlacklistManager
    {
        $blacklistManager = $this->createMock(BlacklistManager::class);

        // 'shit' exist in 'en-base' dict
        $blacklistManager
            ->method('getValues')
            ->willReturnCallback(function (string $key): array {
                $response = [
                    Blacklist::BLACKLISTED_WORDS => self::BLACK_LISTED_WORDS,
                    Blacklist::WHITELISTED_WORDS => self::WHITE_LISTED_WORDS,
                ];

                return $response[$key];
            });

        return $blacklistManager;
    }
}
