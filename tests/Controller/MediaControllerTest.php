<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaControllerTest extends WebTestCase
{
    public function testUpload(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);

        $path = self::$container->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' .
            DIRECTORY_SEPARATOR .
            'media' . DIRECTORY_SEPARATOR . 'default_profile.png';
        $originalName = 'default_profile.png';
        $file = new UploadedFile($path, $originalName, null, UPLOAD_ERR_OK, true);

        $this->client->request('POST', '/api/media/upload', [
            'type' => 'profile',
        ], ['file' => $file]);

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('image', $res);
    }
}
