<?php declare(strict_types = 1);

namespace App\Tests\Validator\Handler;

use App\Communications\DisposableEmailCommunicator;
use App\Communications\GuzzleRestWrapper;
use PHPUnit\Framework\TestCase;

class DisposableEmailCommunicatorTest extends TestCase
{
    public function testDisposableEmail(): void
    {
        $email = 'foobar@0x01.gq';
        $disposableApiLink = 'https://open.kickbox.com/v1/disposable/';

        $rpc = $this->createMock(RestRpcInterface::class);

        $disposableEmail = new DisposableEmailCommunicator($disposableApiLink, $rpc);

        $rpc->method('send')->willReturn(json_encode(false));

        $this->assertEquals($disposableEmail->checkDisposable($email), true);
    }
}
