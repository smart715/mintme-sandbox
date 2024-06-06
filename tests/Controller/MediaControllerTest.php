<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaControllerTest extends WebTestCase
{
    public function testUpload(): void
    {
        $this->register($this->client);

        $originalName = 'foo.png';
        $dir = self::$container->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' .
            DIRECTORY_SEPARATOR .
            'media' . DIRECTORY_SEPARATOR;
        $path = $dir . DIRECTORY_SEPARATOR . 'foo.png';
        copy($dir . DIRECTORY_SEPARATOR . 'default_mintme.svg', $path);
        $file = new UploadedFile($path, $originalName, null, UPLOAD_ERR_OK, true);

        $this->client->request('POST', self::LOCALHOST . '/api/media/upload', [
            'type' => 'profile',
        ], ['file' => $file]);

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('image', $res);
    }
}
