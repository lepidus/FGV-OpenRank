<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;

class FgvOpenRankRenameMigration extends Migration
{
    private const LEGACY_PLUGIN_NAME = 'rankingplugin';
    private const PLUGIN_NAME = 'fgvopenrankplugin';
    private const LEGACY_PRODUCT_TYPE = 'plugins.generic';
    private const LEGACY_PRODUCT = 'rankingPlugin';
    private const LEGACY_TASK_CLASS = 'plugins.generic.rankingPlugin.classes.tasks.RankingCacheUpdateTask';

    public static function hasLegacySettings(): bool
    {
        return Capsule::table('plugin_settings')->where('plugin_name', self::LEGACY_PLUGIN_NAME)->exists();
    }

    public function up()
    {
        $contextIds = Capsule::table('plugin_settings')
            ->where('plugin_name', self::LEGACY_PLUGIN_NAME)
            ->distinct()
            ->pluck('context_id')
            ->all();

        $this->moveSettings();
        $this->retireLegacyVersion();
        $this->forgetLegacyScheduledTask(!empty($contextIds));
        $this->refreshSettingsCache($contextIds);
    }

    public function down()
    {
    }

    private function moveSettings(): void
    {
        $currentKeys = Capsule::table('plugin_settings')
            ->where('plugin_name', self::PLUGIN_NAME)
            ->get(['context_id', 'setting_name'])
            ->map(function ($setting) {
                return $this->getSettingKey($setting);
            })
            ->flip();

        $legacySettings = Capsule::table('plugin_settings')
            ->where('plugin_name', self::LEGACY_PLUGIN_NAME)
            ->get(['context_id', 'setting_name']);

        foreach ($legacySettings as $setting) {
            $query = Capsule::table('plugin_settings')
                ->where('plugin_name', self::LEGACY_PLUGIN_NAME)
                ->where('context_id', $setting->context_id)
                ->where('setting_name', $setting->setting_name);

            if ($currentKeys->has($this->getSettingKey($setting))) {
                $query->delete();
            } else {
                $query->update(['plugin_name' => self::PLUGIN_NAME]);
            }
        }
    }

    private function getSettingKey($setting): string
    {
        return "{$setting->context_id}|{$setting->setting_name}";
    }

    private function retireLegacyVersion(): void
    {
        if (is_dir($this->getLegacyPluginPath())) {
            return;
        }

        Capsule::table('versions')
            ->where('product_type', self::LEGACY_PRODUCT_TYPE)
            ->where('product', self::LEGACY_PRODUCT)
            ->update(['current' => 0]);
    }

    protected function getLegacyPluginPath(): string
    {
        return Core::getBaseDir() . '/plugins/generic/' . self::LEGACY_PRODUCT;
    }

    private function forgetLegacyScheduledTask(bool $tookSettingsOver): void
    {
        Capsule::table('scheduled_tasks')->where('class_name', self::LEGACY_TASK_CLASS)->delete();

        // Acron rebuilds its cached crontab on the next request when the setting is gone,
        // and only then picks up the task of a plugin that has just become enabled.
        $crontab = Capsule::table('plugin_settings')
            ->where('plugin_name', 'acronplugin')
            ->where('setting_name', 'crontab');
        if ($tookSettingsOver || strpos((string) (clone $crontab)->value('setting_value'), self::LEGACY_TASK_CLASS) !== false) {
            $crontab->delete();
            DAORegistry::getDAO('PluginSettingsDAO')->getPluginSettings(0, 'acronplugin');
        }
    }

    private function refreshSettingsCache(array $contextIds): void
    {
        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
        foreach ($contextIds as $contextId) {
            $pluginSettingsDao->getPluginSettings((int) $contextId, self::LEGACY_PLUGIN_NAME);
            $pluginSettingsDao->getPluginSettings((int) $contextId, self::PLUGIN_NAME);
        }
    }
}
