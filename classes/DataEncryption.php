<?php

namespace APP\plugins\generic\rankingPlugin\classes;

use Exception;
use Illuminate\Support\Facades\Crypt;

class DataEncryption
{
    public function encryptString(string $plainText): string
    {
        try {
            return Crypt::encryptString($plainText);
        } catch (Exception $e) {
            throw new Exception('FGV OpenRank - Failed to encrypt string');
        }
    }

    public function decryptString(string $encryptedText): string
    {
        try {
            return Crypt::decryptString($encryptedText);
        } catch (Exception $e) {
            throw new Exception('FGV OpenRank - Failed to decrypt string');
        }
    }
}
