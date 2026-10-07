<?php

namespace APP\plugins\generic\fgvOpenRank\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PKP\core\Core;
use PKP\db\DAORegistry;

class PluginRenameMigration extends Migration
{
    private const LEGACY_PLUGIN_NAME = 'rankingplugin';
    private const PLUGIN_NAME = 'fgvopenrankplugin';
    private const LEGACY_PRODUCT_TYPE = 'plugins.generic';
    private const LEGACY_PRODUCT = 'rankingPlugin';
    private const LEGACY_CACHE_NAMES = [
        'most_recent_submissions',
        'most_read_submissions',
        'most_cited_dois',
        'best_altmetrics_score_dois',
        'trending_submissions',
    ];

    public function up(): void
    {
        $contextIds = DB::table('plugin_settings')
            ->where('plugin_name', self::LEGACY_PLUGIN_NAME)
            ->distinct()
            ->pluck('context_id')
            ->all();

        $this->moveSettings();
        $this->retireLegacyVersion();
        $this->forgetLegacyCaches($contextIds);
        $this->refreshSettingsCache($contextIds);
    }

    public function down(): void
    {
    }

    private function moveSettings(): void
    {
        $currentKeys = DB::table('plugin_settings')
            ->where('plugin_name', self::PLUGIN_NAME)
            ->get(['context_id', 'setting_name'])
            ->map(fn ($setting) => $this->getSettingKey($setting))
            ->flip();

        [$overriddenSettings, $movableSettings] = DB::table('plugin_settings')
            ->where('plugin_name', self::LEGACY_PLUGIN_NAME)
            ->get(['plugin_setting_id', 'context_id', 'setting_name'])
            ->partition(fn ($setting) => $currentKeys->has($this->getSettingKey($setting)));

        foreach ($movableSettings->pluck('plugin_setting_id')->chunk(500) as $settingIds) {
            DB::table('plugin_settings')
                ->whereIn('plugin_setting_id', $settingIds->all())
                ->update(['plugin_name' => self::PLUGIN_NAME]);
        }

        foreach ($overriddenSettings->pluck('plugin_setting_id')->chunk(500) as $settingIds) {
            DB::table('plugin_settings')
                ->whereIn('plugin_setting_id', $settingIds->all())
                ->delete();
        }
    }

    private function getSettingKey(object $setting): string
    {
        return "{$setting->context_id}|{$setting->setting_name}";
    }

    private function retireLegacyVersion(): void
    {
        if (is_dir($this->getLegacyPluginPath())) {
            return;
        }

        DB::table('versions')
            ->where('product_type', self::LEGACY_PRODUCT_TYPE)
            ->where('product', self::LEGACY_PRODUCT)
            ->update(['current' => 0]);
    }

    protected function getLegacyPluginPath(): string
    {
        return Core::getBaseDir() . '/plugins/generic/' . self::LEGACY_PRODUCT;
    }

    private function forgetLegacyCaches(array $contextIds): void
    {
        foreach ($contextIds as $contextId) {
            foreach (self::LEGACY_CACHE_NAMES as $cacheName) {
                Cache::forget(self::LEGACY_PRODUCT . "-{$cacheName}-{$contextId}");
            }
        }
    }

    private function refreshSettingsCache(array $contextIds): void
    {
        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
        foreach ($contextIds as $contextId) {
            $contextId = $contextId === null ? null : (int) $contextId;
            $pluginSettingsDao->getPluginSettings($contextId, self::LEGACY_PLUGIN_NAME);
            $pluginSettingsDao->getPluginSettings($contextId, self::PLUGIN_NAME);
        }
    }
}
