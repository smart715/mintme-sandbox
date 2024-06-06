<?php declare(strict_types = 1);

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\MoneyTransformer;
use App\Tests\Mocks\MockMoneyWrapper;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class MoneyTransformerTest extends TestCase
{

    use MockMoneyWrapper;

    /** @dataProvider transformProvider */
    public function testTransform(string $amount): void
    {
        $m = new Money($amount, new Currency('FOO'));
        $mt = new MoneyTransformer($this->mockMoneyWrapper());
        $this->assertEquals($amount, $mt->transform($m));
    }

    public function transformProvider(): array
    {
        return [['1'], ['-1'], ['0'], ['1000000'], ['-1000000']];
    }

    /** @dataProvider reverseTransformProvider */
    public function testReverseTransform(string $amount, bool $expectException = false): void
    {
        $mt = new MoneyTransformer($this->mockMoneyWrapper());

        if ($expectException) {
            $this->expectException(TransformationFailedException::class);
            $mt->reverseTransform($amount);

            return;
        }

        $this->assertEquals(new Money($amount, new Currency('TOK')), $mt->reverseTransform($amount));
    }

    public function reverseTransformProvider(): array
    {
        return [['1'], ['-1'], ['0'], ['1000000'], ['-1000000'], ['FOO', true]];
    }

    public function testSetSymbol(): void
    {
        $mt = new MoneyTransformer($this->mockMoneyWrapper());

        // Assert symbol is TOK by default
        $this->assertEquals('TOK', $mt->reverseTransform('1')->getCurrency()->getCode());

        $mt->setSymbol('FOO');

        $this->assertEquals('FOO', $mt->reverseTransform('1')->getCurrency()->getCode());
    }
}
