<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\DisposableEmailCommunicator;
use App\Communications\RestRpcInterface;
use PHPUnit\Framework\TestCase;

class DisposableEmailCommunicatorTest extends TestCase
{
    public function testFetchDomains(): void
    {
        $data = [
            "0-180.com",
            "0-420.com",
        ];

        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn(json_encode($data));

        $disposableEmail = new DisposableEmailCommunicator($rpc);
        $domains = $disposableEmail->fetchDomains();

        $this->assertEquals($domains[0], '0-180.com');
        $this->assertEquals($domains[1], '0-420.com');
    }
}
