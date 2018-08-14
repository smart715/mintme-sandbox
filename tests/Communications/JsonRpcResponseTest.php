<?php

namespace App\Tests\Communications;

use App\Communications\JsonRpcResponse;
use PHPUnit\Framework\TestCase;

class JsonRpcResponseTest extends TestCase
{
    public function testParseResponse(): void
    {
        $this->assertInstanceOf(
            JsonRpcResponse::class,
            JsonRpcResponse::parse($this->getJsonResponse())
        );
    }

    public function testParseResponseWrongJsonResponse(): void
    {
        $this->expectException(\Throwable::class);
        JsonRpcResponse::parse('');
    }

    /**
     * @dataProvider resultProvider
     */
    public function testGetResult(array $expected, array $result): void
    {
        $this->assertEquals($expected, $result);
    }

    public function resultProvider(): array
    {
        $responseResult = JsonRpcResponse::parse($this->getJsonResponse());
        $responseError = JsonRpcResponse::parse($this->getErrorJsonResponse());
        return [
            [$this->getJsonResponseAsArray()['result'], $responseResult->getResult()],
            [[], $responseError->getResult()],
        ];
    }

    /**
     * @dataProvider errorProvider
     */
    public function testGetError(array $expected, array $error): void
    {
        $this->assertEquals($expected, $error);
    }

    public function errorProvider(): array
    {
        $responseError = JsonRpcResponse::parse($this->getErrorJsonResponse());
        $responseResult = JsonRpcResponse::parse($this->getJsonResponse());
        return [
            [$this->getErrorJsonResponseAsArray()['error'], $responseError->getError()],
            [[], $responseResult->getError()],
        ];
    }

    /**
     * @dataProvider hasResultProvider
     */
    public function testHasResult(bool $expected, bool $hasResult): void
    {
        $this->assertEquals($expected, $hasResult);
    }

    public function hasResultProvider(): array
    {
        $responseResult = JsonRpcResponse::parse($this->getJsonResponse());
        $responseError = JsonRpcResponse::parse($this->getErrorJsonResponse());
        return [
            [true, $responseResult->hasResult()],
            [false, $responseError->hasResult()],
        ];
    }

    /**
     * @dataProvider hasErrorProvider
     */
    public function testHasError(bool $expected, bool $hasError): void
    {
        $this->assertEquals($expected, $hasError);
    }

    public function hasErrorProvider(): array
    {
        $responseResult = JsonRpcResponse::parse($this->getJsonResponse());
        $responseError = JsonRpcResponse::parse($this->getErrorJsonResponse());
        return [
            [false, $responseResult->hasError()],
            [true, $responseError->hasError()],
        ];
    }

    private function getJsonResponse(): string
    {
        return '{'.
            '"id": "0",'.
            '"jsonrpc": "2.0",'.
            '"result": {'.
                '"fee": 48958481211,'.
                '"tx_hash": "0123456789abcdefghijklmnopqrstuv",'.
                '"tx_key": "abcdefghijklmnopqrstuv0123456789"'.
            '}'.
        '}';
    }

    private function getJsonResponseAsArray(): array
    {
        return [
            "id" => "0",
            "jsonrpc" => "2.0",
            "result" => [
                "fee" => 48958481211,
                "tx_hash" => "0123456789abcdefghijklmnopqrstuv",
                "tx_key" => "abcdefghijklmnopqrstuv0123456789",
            ],
        ];
    }

    private function getErrorJsonResponse(): string
    {
        return '{'.
            '"jsonrpc": "2.0",'.
            '"error": {'.
                '"code": -32601,'.
                '"message": "Method not found"'.
            '},'.
            '"id": "5"'.
        '}';
    }

    private function getErrorJsonResponseAsArray(): array
    {
        return [
            "jsonrpc" => "2.0",
            "error" => [
                "code" => -32601,
                "message" => "Method not found",
            ],
            "id" => "5",
        ];
    }
}
