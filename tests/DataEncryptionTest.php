<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\DataEncryption;
use Exception;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class DataEncryptionTest extends PKPTestCase
{
    private const API_KEY = 'altmetric-api-key-plaintext';

    #[Test]
    public function itShouldEncryptWithTheApplicationKey()
    {
        $encryptedText = (new DataEncryption())->encryptString(self::API_KEY);

        $this->assertNotSame(self::API_KEY, $encryptedText);
        $this->assertSame(self::API_KEY, Crypt::decryptString($encryptedText));
    }

    #[Test]
    public function itShouldDecryptWhatTheApplicationKeyEncrypted()
    {
        $encryptedText = Crypt::encryptString(self::API_KEY);

        $this->assertSame(self::API_KEY, (new DataEncryption())->decryptString($encryptedText));
    }

    #[Test]
    public function itShouldRefuseValuesEncryptedWithAnotherSecret()
    {
        $this->expectException(Exception::class);

        (new DataEncryption())->decryptString('base64:encrypted-with-the-old-api-key-secret');
    }
}
