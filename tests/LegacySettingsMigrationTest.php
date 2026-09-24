<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\migrations\LegacySettingsMigration;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PKP\config\Config;
use PKP\tests\DatabaseTestCase;

class LegacySettingsMigrationTest extends DatabaseTestCase
{
    private const CONTEXT_ID = 990001;
    private const LEGACY_SECRET = 'legacy-secret';
    private const API_KEY = 'altmetric-api-key-plaintext';

    protected function getAffectedTables(): array
    {
        return [...parent::getAffectedTables(), 'plugin_settings'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('journals')->insert([
            'journal_id' => self::CONTEXT_ID,
            'path' => 'rankingPluginMigrationTest',
            'primary_locale' => 'en',
        ]);
    }

    protected function tearDown(): void
    {
        $this->setLegacySecret('');
        DB::table('journals')->where('journal_id', self::CONTEXT_ID)->delete();
        parent::tearDown();
    }

    private function setLegacySecret(string $secret): void
    {
        $configData = &Config::getData();
        $configData['security']['api_key_secret'] = $secret;
    }

    private function encryptWithLegacySecret(string $plainText): string
    {
        $encrypter = new Encrypter(hash('sha256', self::LEGACY_SECRET, true), 'aes-256-cbc');
        return 'base64:' . base64_encode($encrypter->encrypt($plainText));
    }

    private function insertSetting(string $name, string $value, string $type): void
    {
        DB::table('plugin_settings')->insert([
            'plugin_name' => 'rankingplugin',
            'context_id' => self::CONTEXT_ID,
            'setting_name' => $name,
            'setting_value' => $value,
            'setting_type' => $type,
        ]);
    }

    private function getSetting(string $name): ?string
    {
        return DB::table('plugin_settings')
            ->where('plugin_name', 'rankingplugin')
            ->where('context_id', self::CONTEXT_ID)
            ->where('setting_name', $name)
            ->value('setting_value');
    }

    #[Test]
    public function itShouldRenameLocalesMergedSinceOjs33()
    {
        $this->assertSame(
            ['en' => 'Most read', 'pt_BR' => 'Mais lidos', 'es' => 'Más leídos', 'fr' => 'Plus lus'],
            LegacySettingsMigration::convertLocaleKeys([
                'en_US' => 'Most read',
                'pt_BR' => 'Mais lidos',
                'es_ES' => 'Más leídos',
                'fr_FR' => 'Plus lus',
            ])
        );
    }

    #[Test]
    public function itShouldNotLetAnEmptyLegacyValueOverwriteACurrentOne()
    {
        $this->assertSame(
            ['en' => 'Most read'],
            LegacySettingsMigration::convertLocaleKeys(['en' => 'Most read', 'en_US' => ''])
        );
    }

    #[Test]
    public function itShouldConvertStoredLocalizedSettingsAndBeSafeToRunAgain()
    {
        $this->insertSetting('customTitle_trending', json_encode(['en_US' => 'Hot now', 'pt_BR' => 'Em alta']), 'object');
        $this->insertSetting('highlightContent_highlight', serialize(['es_ES' => '<p>Destacado</p>']), 'object');

        (new LegacySettingsMigration())->up();
        (new LegacySettingsMigration())->up();

        $this->assertSame(['en' => 'Hot now', 'pt_BR' => 'Em alta'], json_decode($this->getSetting('customTitle_trending'), true));
        $this->assertSame(['es' => '<p>Destacado</p>'], json_decode($this->getSetting('highlightContent_highlight'), true));
    }

    #[Test]
    public function itShouldReencryptTheLegacyApiKeyWithTheApplicationKey()
    {
        $this->setLegacySecret(self::LEGACY_SECRET);
        $this->insertSetting('altmetricsApiKey_trending', $this->encryptWithLegacySecret(self::API_KEY), 'string');

        (new LegacySettingsMigration())->up();

        $this->assertSame(self::API_KEY, Crypt::decryptString($this->getSetting('altmetricsApiKey_trending')));
    }

    #[Test]
    public function itShouldRemoveTheLegacyApiKeyWhenTheLegacySecretIsGone()
    {
        $this->setLegacySecret('');
        $this->insertSetting('altmetricsApiKey_trending', $this->encryptWithLegacySecret(self::API_KEY), 'string');

        (new LegacySettingsMigration())->up();

        $this->assertSame('', $this->getSetting('altmetricsApiKey_trending'));
    }

    #[Test]
    public function itShouldKeepAnApiKeyAlreadyEncryptedWithTheApplicationKey()
    {
        $encryptedKey = Crypt::encryptString(self::API_KEY);
        $this->insertSetting('altmetricsApiKey_trending', $encryptedKey, 'string');

        (new LegacySettingsMigration())->up();

        $this->assertSame($encryptedKey, $this->getSetting('altmetricsApiKey_trending'));
    }
}
