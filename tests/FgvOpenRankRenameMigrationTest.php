<?php

import('lib.pkp.tests.DatabaseTestCase');
import('plugins.generic.fgvOpenRank.classes.migrations.FgvOpenRankRenameMigration');

use Illuminate\Database\Capsule\Manager as Capsule;

class FgvOpenRankRenameMigrationTest extends DatabaseTestCase
{
    private const FIRST_CONTEXT_ID = 990011;
    private const SECOND_CONTEXT_ID = 990012;
    private const LEGACY_PLUGIN_NAME = 'rankingplugin';
    private const PLUGIN_NAME = 'fgvopenrankplugin';
    private const LEGACY_TASK_CLASS = 'plugins.generic.rankingPlugin.classes.tasks.RankingCacheUpdateTask';
    private const MISSING_LEGACY_PATH = '/nonexistent/plugins/generic/rankingPlugin';

    protected function getAffectedTables()
    {
        return ['plugin_settings', 'versions', 'scheduled_tasks'];
    }

    private function createMigration(string $legacyPluginPath): FgvOpenRankRenameMigration
    {
        $migration = new class () extends FgvOpenRankRenameMigration {
            public $testLegacyPluginPath;

            protected function getLegacyPluginPath(): string
            {
                return $this->testLegacyPluginPath;
            }
        };
        $migration->testLegacyPluginPath = $legacyPluginPath;
        return $migration;
    }

    private function insertSetting(string $pluginName, int $contextId, string $name, string $value): void
    {
        Capsule::table('plugin_settings')->insert([
            'plugin_name' => $pluginName,
            'context_id' => $contextId,
            'setting_name' => $name,
            'setting_value' => $value,
            'setting_type' => 'string',
        ]);
    }

    private function getSettings(string $pluginName, int $contextId): array
    {
        return Capsule::table('plugin_settings')
            ->where('plugin_name', $pluginName)
            ->where('context_id', $contextId)
            ->orderBy('setting_name')
            ->pluck('setting_value', 'setting_name')
            ->all();
    }

    private function insertLegacyVersion(): void
    {
        Capsule::table('versions')->where('product_type', 'plugins.generic')->where('product', 'rankingPlugin')->delete();
        Capsule::table('versions')->insert([
            'major' => 0,
            'minor' => 0,
            'revision' => 5,
            'build' => 3,
            'date_installed' => '2026-09-22 00:00:00',
            'current' => 1,
            'product_type' => 'plugins.generic',
            'product' => 'rankingPlugin',
            'product_class_name' => 'RankingPlugin',
            'lazy_load' => 1,
            'sitewide' => 0,
        ]);
    }

    private function isLegacyVersionCurrent(): bool
    {
        return (bool) Capsule::table('versions')
            ->where('product_type', 'plugins.generic')
            ->where('product', 'rankingPlugin')
            ->value('current');
    }

    private function setAcronCrontab(string $taskClass): void
    {
        Capsule::table('plugin_settings')->where('plugin_name', 'acronplugin')->where('setting_name', 'crontab')->delete();
        Capsule::table('plugin_settings')->insert([
            'plugin_name' => 'acronplugin',
            'context_id' => 0,
            'setting_name' => 'crontab',
            'setting_value' => serialize([['className' => $taskClass, 'frequency' => ['hour' => '0'], 'args' => []]]),
            'setting_type' => 'object',
        ]);
    }

    private function hasAcronCrontab(): bool
    {
        return Capsule::table('plugin_settings')->where('plugin_name', 'acronplugin')->where('setting_name', 'crontab')->exists();
    }

    public function testShouldMoveTheSettingsOfEveryContextToTheNewPluginName()
    {
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'enabled', '1');
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'itemsPerTab_mostRead', '6');
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::SECOND_CONTEXT_ID, 'enabled', '0');

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertSame(['enabled' => '1', 'itemsPerTab_mostRead' => '6'], $this->getSettings(self::PLUGIN_NAME, self::FIRST_CONTEXT_ID));
        $this->assertSame(['enabled' => '0'], $this->getSettings(self::PLUGIN_NAME, self::SECOND_CONTEXT_ID));
        $this->assertSame([], $this->getSettings(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID));
        $this->assertSame([], $this->getSettings(self::LEGACY_PLUGIN_NAME, self::SECOND_CONTEXT_ID));
    }

    public function testShouldKeepTheNewValueWhenBothNamesHoldTheSameSetting()
    {
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'itemsPerTab_mostRead', '6');
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'mostReadDays_mostRead', '30');
        $this->insertSetting(self::PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'itemsPerTab_mostRead', '8');

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertSame(
            ['itemsPerTab_mostRead' => '8', 'mostReadDays_mostRead' => '30'],
            $this->getSettings(self::PLUGIN_NAME, self::FIRST_CONTEXT_ID)
        );
        $this->assertSame([], $this->getSettings(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID));
    }

    public function testShouldBeSafeToRunAgain()
    {
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'enabled', '1');

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();
        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertSame(['enabled' => '1'], $this->getSettings(self::PLUGIN_NAME, self::FIRST_CONTEXT_ID));
    }

    public function testShouldTellWhetherLegacySettingsAreLeft()
    {
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'enabled', '1');
        $this->assertTrue(FgvOpenRankRenameMigration::hasLegacySettings());

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertFalse(FgvOpenRankRenameMigration::hasLegacySettings());
    }

    public function testShouldRetireTheLegacyVersionWhenItsDirectoryIsGone()
    {
        $this->insertLegacyVersion();

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertFalse($this->isLegacyVersionCurrent());
    }

    public function testShouldKeepTheLegacyVersionWhileItsDirectoryExistsSoItCanStillBeDeleted()
    {
        $this->insertLegacyVersion();

        $this->createMigration(__DIR__)->up();

        $this->assertTrue($this->isLegacyVersionCurrent());
    }

    public function testShouldRemoveTheLastRunOfTheLegacyScheduledTask()
    {
        Capsule::table('scheduled_tasks')->where('class_name', self::LEGACY_TASK_CLASS)->delete();
        Capsule::table('scheduled_tasks')->insert(['class_name' => self::LEGACY_TASK_CLASS, 'last_run' => '2026-09-30 00:00:00']);

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertFalse(Capsule::table('scheduled_tasks')->where('class_name', self::LEGACY_TASK_CLASS)->exists());
    }

    public function testShouldDropAnAcronCrontabThatStillPointsToTheLegacyTask()
    {
        $this->setAcronCrontab(self::LEGACY_TASK_CLASS);

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertFalse($this->hasAcronCrontab());
    }

    public function testShouldDropTheAcronCrontabWhenItTakesTheLegacySettingsOver()
    {
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'enabled', '1');
        $this->setAcronCrontab('plugins.generic.usageStats.UsageStatsLoader');

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertFalse($this->hasAcronCrontab());
    }

    public function testShouldKeepAnAcronCrontabThatNoLongerPointsToTheLegacyTask()
    {
        $this->setAcronCrontab('plugins.generic.fgvOpenRank.classes.tasks.RankingCacheUpdateTask');

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertTrue($this->hasAcronCrontab());
    }

    public function testShouldRunWhenTheInstallerCreatesItFromUpgradeXml()
    {
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'enabled', '1');

        $migration = new FgvOpenRankRenameMigration();
        $migration->up();

        $this->assertSame(['enabled' => '1'], $this->getSettings(self::PLUGIN_NAME, self::FIRST_CONTEXT_ID));
    }
}
