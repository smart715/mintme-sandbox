<?php declare(strict_types = 1);

namespace App\Utils\Encrypt;

class HMACSHAOneEncrypt implements EncryptInterface
{
    /** @var string **/
    protected $key;

    /** @var string **/
    protected $data;

    public function __construct(string $key, string $data)
    {
        $this->key = $key;
        $this->data = $data;
    }

    public function encrypt(): string
    {
        // Adjust key to exactly 64 bytes
        if (strlen($this->key) > 64) {
            $this->key = str_pad(sha1($this->key, true), 64, chr(0));
        }

        if (strlen($this->key) < 64) {
            $this->key = str_pad($this->key, 64, chr(0));
        }

        // Outter and Inner pad
        $opad = str_repeat(chr(0x5C), 64);
        $ipad = str_repeat(chr(0x36), 64);

        // Xor key with opad & ipad
        for ($i = 0; $i < strlen($this->key); $i++) {
            $opad[$i] ^= $this->key[$i];
            $ipad[$i] ^= $this->key[$i];
        }

        return sha1($opad.sha1($ipad.$this->data, true));
    }
}
