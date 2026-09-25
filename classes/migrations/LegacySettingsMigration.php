<?php

namespace APP\plugins\generic\rankingPlugin\classes\migrations;

use APP\plugins\generic\rankingPlugin\classes\settings\AltmetricsApiKey;
use Exception;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PKP\config\Config;
use PKP\db\DAORegistry;
use PKP\migration\upgrade\v3_4_0\MergeLocalesMigration;
use PKP\migration\upgrade\v3_5_0\I9707_WeblateUILocales;

class LegacySettingsMigration extends Migration
{
    private const PLUGIN_NAME = 'rankingplugin';
    private const LOCALIZED_SETTING_PREFIXES = ['customTitle_', 'customDescription_', 'highlightContent_'];
    private const LEGACY_ENCRYPTION_CIPHER = 'aes-256-cbc';
    private const LEGACY_BASE64_PREFIX = 'base64:';
    private const LEGACY_SECRET_SETTING = 'api_key_secret';

    private array $changedContextIds = [];

    public function up(): void
    {
        $this->convertLocalizedSettings();
        $this->reencryptAltmetricsApiKeys();
        $this->refreshSettingsCache();
    }

    public function down(): void
    {
    }

    public static function convertLocaleKeys(array $values): array
    {
        $convertedValues = [];
        foreach ($values as $locale => $value) {
            $newLocale = self::convertLocale((string) $locale);
            if (!array_key_exists($newLocale, $convertedValues) || empty($convertedValues[$newLocale])) {
                $convertedValues[$newLocale] = $value;
            }
        }

        return $convertedValues;
    }

    private function convertLocalizedSettings(): void
    {
        $settings = DB::table('plugin_settings')
            ->where('plugin_name', self::PLUGIN_NAME)
            ->where(function ($query) {
                foreach (self::LOCALIZED_SETTING_PREFIXES as $prefix) {
                    $query->orWhere('setting_name', 'like', $prefix . '%');
                }
            })
            ->get();

        foreach ($settings as $setting) {
            $values = $this->decode($setting->setting_value);
            if (!is_array($values)) {
                continue;
            }

            $convertedValues = self::convertLocaleKeys($values);
            if ($convertedValues === $values) {
                continue;
            }

            DB::table('plugin_settings')
                ->where('plugin_setting_id', $setting->plugin_setting_id)
                ->update(['setting_value' => json_encode($convertedValues), 'setting_type' => 'object']);
            $this->changedContextIds[] = $setting->context_id;
        }
    }

    private function reencryptAltmetricsApiKeys(): void
    {
        $settings = DB::table('plugin_settings')
            ->where('plugin_name', self::PLUGIN_NAME)
            ->where('setting_name', AltmetricsApiKey::SETTING_NAME)
            ->where('setting_value', 'like', self::LEGACY_BASE64_PREFIX . '%')
            ->get();

        foreach ($settings as $setting) {
            try {
                $newValue = Crypt::encryptString($this->decryptLegacyString($setting->setting_value));
            } catch (Exception $e) {
                $newValue = '';
            }

            DB::table('plugin_settings')
                ->where('plugin_setting_id', $setting->plugin_setting_id)
                ->update(['setting_value' => $newValue]);
            $this->changedContextIds[] = $setting->context_id;
        }
    }

    private function refreshSettingsCache(): void
    {
        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
        foreach (array_unique($this->changedContextIds) as $contextId) {
            $pluginSettingsDao->getPluginSettings($contextId === null ? null : (int) $contextId, self::PLUGIN_NAME);
        }
    }

    private function decryptLegacyString(string $encryptedText): string
    {
        $secret = (string) Config::getVar('security', self::LEGACY_SECRET_SETTING);
        if ($secret === '') {
            throw new Exception('FGV OpenRank - The legacy encryption secret is not configured');
        }

        $encrypter = new Encrypter(hash('sha256', $secret, true), self::LEGACY_ENCRYPTION_CIPHER);
        $payload = base64_decode(substr($encryptedText, strlen(self::LEGACY_BASE64_PREFIX)));

        return $encrypter->decrypt($payload);
    }

    private static function convertLocale(string $locale): string
    {
        $locale = MergeLocalesMigration::getAffectedLocales()[$locale] ?? $locale;
        return I9707_WeblateUILocales::getAffectedLocales()[$locale] ?? $locale;
    }

    private function decode(?string $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        $unserialized = @unserialize($value, ['allowed_classes' => false]);
        return $unserialized === false ? null : $unserialized;
    }
}
