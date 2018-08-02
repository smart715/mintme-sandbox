<?php

namespace App\Tests\Form;

use App\Entity\Profile;
use App\Entity\Token;
use App\Form\TokenFormType;
use Symfony\Component\Form\Test\TypeTestCase;

class TokenFormTypeTest extends TypeTestCase
{
    /** @dataProvider formDataProvider */
    public function testSubmitValidData(array $formData): void
    {
        $tokenToCompare = new Token();
        $form = $this->factory->create(TokenFormType::class, $tokenToCompare);

        $token = new Token();
        $token = $this->populateTokenWithData($token, $formData);
        
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($token, $tokenToCompare);

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function formDataProvider(): array
    {
        return [
            [[
                'name' => 'ABC',
                'websiteUrl' => '',
                'facebookUrl' => '',
                'youtubeUrl' => '',
                'description' => '',
            ]],
            [[
                'name' => 'ABC',
                'websiteUrl' => 'https://website.com',
                'facebookUrl' => 'https://facebook.com/username',
                'youtubeUrl' => 'https://youtube.com/channel',
                'description' => 'This is description for ABC token',
            ]],
        ];
    }

    private function populateTokenWithData(Token $token, array $formData): Token
    {
        foreach ($formData as $property => $value) {
            $setProperty = 'set'.ucfirst($property);
            $token->$setProperty($value);
        }
        return $token;
    }
}
