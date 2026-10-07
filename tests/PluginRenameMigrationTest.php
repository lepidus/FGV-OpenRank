<?php

namespace APP\plugins\generic\fgvOpenRank\tests;

use APP\plugins\generic\fgvOpenRank\classes\migrations\PluginRenameMigration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PKP\install\Installer;
use PKP\tests\DatabaseTestCase;

class PluginRenameMigrationTest extends DatabaseTestCase
{
    private const FIRST_CONTEXT_ID = 990011;
    private const SECOND_CONTEXT_ID = 990012;
    private const LEGACY_PLUGIN_NAME = 'rankingplugin';
    private const PLUGIN_NAME = 'fgvopenrankplugin';
    private const MISSING_LEGACY_PATH = '/nonexistent/plugins/generic/rankingPlugin';

    protected function getAffectedTables(): array
    {
        return [...parent::getAffectedTables(), 'plugin_settings', 'versions'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([self::FIRST_CONTEXT_ID, self::SECOND_CONTEXT_ID] as $contextId) {
            DB::table('journals')->insert([
                'journal_id' => $contextId,
                'path' => "fgvOpenRankRenameTest{$contextId}",
                'primary_locale' => 'en',
            ]);
        }
    }

    protected function tearDown(): void
    {
        DB::table('journals')->whereIn('journal_id', [self::FIRST_CONTEXT_ID, self::SECOND_CONTEXT_ID])->delete();
        parent::tearDown();
    }

    private function createMigration(string $legacyPluginPath): PluginRenameMigration
    {
        return new class ($legacyPluginPath) extends PluginRenameMigration {
            public function __construct(private string $testLegacyPluginPath)
            {
            }

            protected function getLegacyPluginPath(): string
            {
                return $this->testLegacyPluginPath;
            }
        };
    }

    private function insertSetting(string $pluginName, int $contextId, string $name, string $value): void
    {
        DB::table('plugin_settings')->insert([
            'plugin_name' => $pluginName,
            'context_id' => $contextId,
            'setting_name' => $name,
            'setting_value' => $value,
            'setting_type' => 'string',
        ]);
    }

    private function getSettings(string $pluginName, int $contextId): array
    {
        return DB::table('plugin_settings')
            ->where('plugin_name', $pluginName)
            ->where('context_id', $contextId)
            ->orderBy('setting_name')
            ->pluck('setting_value', 'setting_name')
            ->all();
    }

    private function insertLegacyVersion(): void
    {
        DB::table('versions')->where('product_type', 'plugins.generic')->where('product', 'rankingPlugin')->delete();
        DB::table('versions')->insert([
            'major' => 1,
            'minor' => 0,
            'revision' => 0,
            'build' => 0,
            'date_installed' => '2026-09-29 00:00:00',
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
        return (bool) DB::table('versions')
            ->where('product_type', 'plugins.generic')
            ->where('product', 'rankingPlugin')
            ->value('current');
    }

    #[Test]
    public function itShouldMoveTheSettingsOfEveryContextToTheNewPluginName()
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

    #[Test]
    public function itShouldKeepTheNewValueWhenBothNamesHoldTheSameSetting()
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

    #[Test]
    public function itShouldBeSafeToRunAgain()
    {
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'enabled', '1');

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();
        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertSame(['enabled' => '1'], $this->getSettings(self::PLUGIN_NAME, self::FIRST_CONTEXT_ID));
    }

    #[Test]
    public function itShouldRetireTheLegacyVersionWhenItsDirectoryIsGone()
    {
        $this->insertLegacyVersion();

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertFalse($this->isLegacyVersionCurrent());
    }

    #[Test]
    public function itShouldKeepTheLegacyVersionWhileItsDirectoryExistsSoItCanStillBeDeleted()
    {
        $this->insertLegacyVersion();

        $this->createMigration(__DIR__)->up();

        $this->assertTrue($this->isLegacyVersionCurrent());
    }

    #[Test]
    public function itShouldForgetTheCachesStoredUnderTheLegacyKeys()
    {
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'enabled', '1');
        $legacyKey = 'rankingPlugin-most_read_submissions-' . self::FIRST_CONTEXT_ID;
        Cache::forever($legacyKey, [['id' => 1]]);

        $this->createMigration(self::MISSING_LEGACY_PATH)->up();

        $this->assertNull(Cache::get($legacyKey));
    }

    #[Test]
    public function itShouldRunWhenTheInstallerCreatesItFromUpgradeXml()
    {
        $this->insertSetting(self::LEGACY_PLUGIN_NAME, self::FIRST_CONTEXT_ID, 'enabled', '1');

        $migration = new PluginRenameMigration($this->createMock(Installer::class), ['class' => PluginRenameMigration::class]);
        $migration->up();

        $this->assertSame(['enabled' => '1'], $this->getSettings(self::PLUGIN_NAME, self::FIRST_CONTEXT_ID));
    }
}
