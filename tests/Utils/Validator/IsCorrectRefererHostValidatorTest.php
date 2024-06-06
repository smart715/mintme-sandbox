<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Utils\Validator\IsCorrectRefererHostValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class IsCorrectRefererHostValidatorTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     * @param array|string|null $refererHeader
     */
    public function testValid($refererHeader, string $host, bool $result): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_referer' => $refererHeader]);

        $validator = new IsCorrectRefererHostValidator($request, $host);

        $this->assertEquals($result, $validator->validate());
        $this->assertEquals('Incorrect referer host', $validator->getMessage());
    }

    public function dataProvider(): array
    {
        return [
            'referer header is null' => [
                'refererHeader' => null,
                'host' => 'microsoft.com',
                'result' => false,
            ],
            'referer is array with wrong referer url' => [
                'refererHeader' => ['https://www.anothersoft.com/'],
                'host' => 'microsoft.com',
                'result' => false,
            ],
            'referer is empty array' => [
                'refererHeader' => [],
                'host' => 'microsoft.com',
                'result' => false,
            ],
            'referer is array with correct referer url' => [
                'referHeader' => ['https://www.microsoft.com/'],
                'host' => 'microsoft.com',
                'result' => true,
            ],
            'referer is array with correct referer url without www' => [
                'referHeader' => ['https://microsoft.com/'],
                'host' => 'microsoft.com',
                'result' => true,
            ],
            'referer is string with correct referer url' => [
                'referHeader' => 'https://discord.com/',
                'host' => 'discord.com',
                'result' => true,
            ],
            'referer is string with incorrect referer url' => [
                'referHeader' => 'https://discord.com/',
                'host' => 'skype.com',
                'result' => false,
            ],
            'referer is string with incorrect referer url and without http scheme' => [
                'referHeader' => 'discord.com',
                'host' => 'skype.com',
                'result' => false,
            ],
        ];
    }
}
