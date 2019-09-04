<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\DisposableEmailCommunicator;
use App\Communications\RestRpcInterface;
use PHPUnit\Framework\TestCase;

class DisposableEmailCommunicatorTest extends TestCase
{
    public function testDisposableEmail(): void
    {
        $email = 'foobar@0x01.gq';

        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn(json_encode(['disposable' => false]));

        $disposableEmail = new DisposableEmailCommunicator($rpc);

        $this->assertEquals($disposableEmail->checkDisposable($email), false);
    }
}
