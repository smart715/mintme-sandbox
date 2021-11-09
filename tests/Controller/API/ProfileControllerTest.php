<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Tests\Controller\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    public function testValidateZipCode(): void
    {
        $this->register($this->client);

        $this->client->request('POST', '/api/profile/validate-zip-code', [
            'country' => 'EG',
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertTrue($res['hasPattern']);
        $this->assertEquals('(\d\d\d\d\d)', $res['pattern']);
    }

    public function testValidateZipCodeWithEmpty(): void
    {
        $this->register($this->client);

        $this->client->request('POST', '/api/profile/validate-zip-code', [
            'country' => '',
        ]);
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertFalse($res['hasPattern']);
        $this->assertEquals('', $res['pattern']);
    }
}
