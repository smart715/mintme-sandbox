<?php declare(strict_types = 1);

namespace App\Tests\Validator\Handler;

use App\Validator\Handler\DisposableEmailApiHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClient;

class DisposableEmailApiHandlerTest extends TestCase
{
    public function testDisposableEmail(): void
    {
        $inavalitEmail = 'foobar@0x01.gq';
        $noramlEmail = 'test@gmail.com';
        $disposableApiLink = 'https://open.kickbox.com/v1/disposable/';
        $disposableEmail = new DisposableEmailApiHandler($disposableApiLink);

        $inavaliEmaildAssertion = $disposableEmail->checkDisposable($inavalitEmail);
        $this->assertEquals($inavaliEmaildAssertion, true);

        $normaEmaillAssertion = $disposableEmail->checkDisposable($noramlEmail);
        $this->assertEquals($normaEmaillAssertion, false);

        $notStringAssertion = $disposableEmail->checkDisposable(floatval(10));
        $this->assertEquals($notStringAssertion, false);

        $nullAssertion = $disposableEmail->checkDisposable(null);
        $this->assertEquals($nullAssertion, false);
    }
}
