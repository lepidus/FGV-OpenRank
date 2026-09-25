<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\clients\Altmetrics;
use APP\plugins\generic\rankingPlugin\classes\DataEncryption;
use APP\plugins\generic\rankingPlugin\classes\settings\AltmetricsApiKey;
use APP\plugins\generic\rankingPlugin\RankingPlugin;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class AltmetricsApiKeyTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;
    private const ISSN = '1234-5678';

    private function buildPluginMock(array &$settings)
    {
        $plugin = $this->createMock(RankingPlugin::class);
        $plugin->method('getSetting')
            ->willReturnCallback(function ($contextId, $key) use (&$settings) {
                return $settings[$key] ?? null;
            });
        $plugin->method('updateSetting')
            ->willReturnCallback(function ($contextId, $key, $value) use (&$settings) {
                $settings[$key] = $value;
                return true;
            });
        return $plugin;
    }

    private function buildApiKey(array &$settings, $dataEncryption = null, $altmetricsClient = null): AltmetricsApiKey
    {
        return new AltmetricsApiKey($this->buildPluginMock($settings), self::CONTEXT_ID, $dataEncryption, $altmetricsClient);
    }

    #[Test]
    public function itShouldStoreEncryptedApiKeyWhenProvided()
    {
        $settings = [];
        $encryption = $this->createMock(DataEncryption::class);
        $encryption->expects($this->once())
            ->method('encryptString')
            ->with('my-plaintext-key')
            ->willReturn('encrypted-value');

        $this->buildApiKey($settings, $encryption)
            ->save(['altmetricsApiKey' => 'my-plaintext-key', 'removeAltmetricsApiKey' => 'false']);

        $this->assertSame('encrypted-value', $settings[AltmetricsApiKey::SETTING_NAME]);
    }

    #[Test]
    public function itShouldClearApiKeyWhenRemoveCheckboxIsTrue()
    {
        $settings = [AltmetricsApiKey::SETTING_NAME => 'previous-key'];
        $encryption = $this->createMock(DataEncryption::class);
        $encryption->expects($this->never())->method('encryptString');

        $this->buildApiKey($settings, $encryption)
            ->save(['altmetricsApiKey' => '', 'removeAltmetricsApiKey' => 'true']);

        $this->assertSame('', $settings[AltmetricsApiKey::SETTING_NAME]);
    }

    #[Test]
    public function itShouldPreserveExistingKeyWhenBothEmpty()
    {
        $settings = [AltmetricsApiKey::SETTING_NAME => 'previous-key'];
        $encryption = $this->createMock(DataEncryption::class);
        $encryption->expects($this->never())->method('encryptString');

        $this->buildApiKey($settings, $encryption)
            ->save(['altmetricsApiKey' => '', 'removeAltmetricsApiKey' => 'false']);

        $this->assertSame('previous-key', $settings[AltmetricsApiKey::SETTING_NAME]);
    }

    #[Test]
    public function itShouldFailValidationWhenAltmetricRejectsTheKey()
    {
        $settings = [];
        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->once())
            ->method('fetchBestScoreSubmissions')
            ->with(self::ISSN, $this->anything(), 'bad-key')
            ->willThrowException(new Exception(__('plugins.generic.rankingPlugin.client.altmetrics.clientError')));

        $previousErrorLog = ini_set('error_log', '/dev/null');
        try {
            $errors = $this->buildApiKey($settings, null, $altmetricsClient)
                ->validate(['altmetricsApiKey' => 'bad-key'], self::ISSN);
        } finally {
            ini_set('error_log', $previousErrorLog);
        }

        $this->assertArrayHasKey('altmetricsApiKey', $errors);
        $this->assertArrayNotHasKey(AltmetricsApiKey::SETTING_NAME, $settings);
    }

    #[Test]
    public function itShouldPassValidationWhenAltmetricAcceptsTheKey()
    {
        $settings = [];
        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->once())
            ->method('fetchBestScoreSubmissions')
            ->with(self::ISSN, $this->anything(), 'good-key')
            ->willReturn(['results' => []]);

        $errors = $this->buildApiKey($settings, null, $altmetricsClient)
            ->validate(['altmetricsApiKey' => 'good-key'], self::ISSN);

        $this->assertSame([], $errors);
    }

    #[Test]
    public function itShouldNotCallAltmetricWhenKeyIsBeingRemoved()
    {
        $settings = [AltmetricsApiKey::SETTING_NAME => 'previous-key'];
        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->never())->method('fetchBestScoreSubmissions');

        $errors = $this->buildApiKey($settings, null, $altmetricsClient)
            ->validate(['altmetricsApiKey' => 'some-key', 'removeAltmetricsApiKey' => 'true'], self::ISSN);

        $this->assertSame([], $errors);
    }

    #[Test]
    public function itShouldNotCallAltmetricWhenTheJournalHasNoIssn()
    {
        $settings = [];
        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->never())->method('fetchBestScoreSubmissions');

        $errors = $this->buildApiKey($settings, null, $altmetricsClient)
            ->validate(['altmetricsApiKey' => 'some-key'], null);

        $this->assertSame([], $errors);
    }

    #[Test]
    public function itShouldReportAStoredKeyOnlyWhenItStillDecrypts()
    {
        $settings = [AltmetricsApiKey::SETTING_NAME => 'base64:encrypted-with-the-old-api-key-secret'];
        $this->assertFalse($this->buildApiKey($settings, new DataEncryption())->has());

        $settings = [AltmetricsApiKey::SETTING_NAME => (new DataEncryption())->encryptString('valid-key')];
        $this->assertTrue($this->buildApiKey($settings, new DataEncryption())->has());
    }

    #[Test]
    public function itShouldReturnTheDecryptedKey()
    {
        $settings = [AltmetricsApiKey::SETTING_NAME => (new DataEncryption())->encryptString('valid-key')];

        $this->assertSame('valid-key', $this->buildApiKey($settings)->get());
    }

    #[Test]
    public function itShouldReturnNoKeyWhenNothingIsStored()
    {
        $settings = [AltmetricsApiKey::SETTING_NAME => ''];

        $this->assertNull($this->buildApiKey($settings)->get());
        $this->assertFalse($this->buildApiKey($settings)->has());
    }

    #[Test]
    public function itShouldReturnNoKeyWhenDecryptionFails()
    {
        $settings = [AltmetricsApiKey::SETTING_NAME => 'base64:encrypted-with-the-old-api-key-secret'];

        $previousErrorLog = ini_set('error_log', '/dev/null');
        try {
            $this->assertNull($this->buildApiKey($settings, new DataEncryption())->get());
        } finally {
            ini_set('error_log', $previousErrorLog);
        }
    }
}
