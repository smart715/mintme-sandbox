<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\TokenReleasePeriod;
use App\Validator\Constraints\TokenReleasePeriodValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class TokenReleasePeriodValidatorTest extends ConstraintValidatorTestCase
{
    private const RELEASE_PERIOD_VALIDATION_RULE = "/^(0|[1-3]|[1-2][0,5]|[3,4,5]0)$/";
    private const MIN_RELEASE_PERIOD = 1;
    private const MAX_RELEASE_PERIOD = 50;
    private const FULL_RELEASE_PERIOD = 0;
    private const MIN_TOKEN_RELEASED = 20;
    private const MAX_TOKEN_RELEASED = 99;
    private const FULL_TOKEN_RELEASED = 100;

    private const VALID_TOKEN_RELEASED = 20;
    private const VALID_RELEASE_PERIOD = 20;
    protected function createValidator(): TokenReleasePeriodValidator
    {
        return new TokenReleasePeriodValidator();
    }

    public function testInvalidConstraint(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate([], $this->createMock(Constraint::class));
    }

    /** @dataProvider getValidReleasePeriod */
    public function testValidReleasePeriod(array $value): void
    {
        $this->validator->validate($value, $this->mockConstraint());
        $this->assertNoViolation();
    }

    /** @dataProvider getInvalidReleasePeriod */
    public function testInvalidInputs(array $value): void
    {
        $constraint = $this->mockConstraint();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->invalidTokenReleasePeriodmessage)
            ->assertRaised();
    }

    /** @dataProvider getOutRangeReleasePeriod */
    public function testOutRangeReleasePeriod(array $value): void
    {
        $constraint = $this->mockConstraint();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->invalidTokenReleasePeriodmessage)
            ->buildNextViolation($constraint->tokenReleasemessage)
            ->setParameter('{{min}}', $constraint->minReleasePeriod)
            ->setParameter('{{max}}', $constraint->maxReleasePeriod)
            ->assertRaised();
    }

    /** @dataProvider getOutRangeTokenReleased */
    public function testOutRangeTokenReleased(array $value): void
    {
        $constraint = $this->mockConstraint();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->tokenReleasemessage)
            ->setParameter('{{min}}', $constraint->minTokenReleased)
            ->setParameter('{{max}}', $constraint->maxTokenReleased)
            ->assertRaised();
    }

    /** @dataProvider getInvalidFullReleasePeriod */
    public function testInvalidFullReleasePeriod(array $value): void
    {
        $constraint = $this->mockConstraint();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->fullTokenReleasePeriodMessage)
            ->setParameter('{{period}}', $constraint->fullReleasePeriod)
            ->setParameter('{{released}}', $constraint->fullTokenReleased)
            ->assertRaised();
    }

    /** @dataProvider getInvalidFullReleaseToken */
    public function testInvalidFullReleaseToken(array $value): void
    {
        $constraint = $this->mockConstraint();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->fullTokenReleaseMessage)
            ->setParameter('{{period}}', $constraint->fullReleasePeriod)
            ->setParameter('{{released}}', $constraint->fullTokenReleased)
            ->assertRaised();
    }

    public function getValidReleasePeriod(): array
    {
        return [
            [[self::VALID_TOKEN_RELEASED, self::MIN_RELEASE_PERIOD]],
            [[self::VALID_TOKEN_RELEASED, 2]],
            [[self::VALID_TOKEN_RELEASED, 3]],
            [[self::VALID_TOKEN_RELEASED, 10]],
            [[self::VALID_TOKEN_RELEASED, 15]],
            [[self::VALID_TOKEN_RELEASED, self::VALID_RELEASE_PERIOD]],
            [[self::VALID_TOKEN_RELEASED, 25]],
            [[self::VALID_TOKEN_RELEASED, 30]],
            [[self::VALID_TOKEN_RELEASED, 40]],
            [[self::VALID_TOKEN_RELEASED, self::MAX_RELEASE_PERIOD]],
        ];
    }

    public function getInvalidReleasePeriod(): array
    {
        return [
            [[self::VALID_TOKEN_RELEASED, 4]],
            [[self::VALID_TOKEN_RELEASED, 13]],
            [[self::VALID_TOKEN_RELEASED, 27]],
            [[self::VALID_TOKEN_RELEASED, 45]],
        ];
    }

    public function getOutRangeReleasePeriod(): array
    {
        return [
            [[self::VALID_TOKEN_RELEASED, self::MAX_RELEASE_PERIOD + 1]],
            [[self::VALID_TOKEN_RELEASED, self::MIN_RELEASE_PERIOD - 2]],
        ];
    }

    public function getOutRangeTokenReleased(): array
    {
        return [
            [[self::MAX_TOKEN_RELEASED + 2, self::VALID_RELEASE_PERIOD]],
            [[self::MIN_TOKEN_RELEASED - 1, self::VALID_RELEASE_PERIOD]],
        ];
    }

    public function getInvalidFullReleasePeriod(): array
    {
        return [[[self::FULL_TOKEN_RELEASED, self::VALID_RELEASE_PERIOD]]];
    }

    public function getInvalidFullReleaseToken(): array
    {
        return [[[self::VALID_TOKEN_RELEASED, self::FULL_RELEASE_PERIOD]]];
    }

    public function mockConstraint(): TokenReleasePeriod
    {
        return new TokenReleasePeriod([
            "validReleasePeriod" => self::RELEASE_PERIOD_VALIDATION_RULE,
            "minReleasePeriod" => self::MIN_RELEASE_PERIOD,
            "maxReleasePeriod" => self::MAX_RELEASE_PERIOD,
            "fullReleasePeriod" => self::FULL_RELEASE_PERIOD,
            "minTokenReleased" => self::MIN_TOKEN_RELEASED,
            "maxTokenReleased" => self::MAX_TOKEN_RELEASED,
            "fullTokenReleased" => self::FULL_TOKEN_RELEASED,
        ]);
    }
}
