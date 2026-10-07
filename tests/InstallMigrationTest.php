<?php

namespace APP\plugins\generic\fgvOpenRank\tests;

use APP\plugins\generic\fgvOpenRank\classes\migrations\InstallMigration;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\DatabaseTestCase;

class InstallMigrationTest extends DatabaseTestCase
{
    private const CONTEXT_ID = 990021;

    protected function getAffectedTables(): array
    {
        return [...parent::getAffectedTables(), 'plugin_settings', 'versions'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('journals')->insert([
            'journal_id' => self::CONTEXT_ID,
            'path' => 'fgvOpenRankInstallTest',
            'primary_locale' => 'en',
        ]);
    }

    protected function tearDown(): void
    {
        DB::table('journals')->where('journal_id', self::CONTEXT_ID)->delete();
        parent::tearDown();
    }

    #[Test]
    public function itShouldConvertSettingsStoredByTheOjs33VersionUnderTheLegacyName()
    {
        DB::table('plugin_settings')->insert([
            'plugin_name' => 'rankingplugin',
            'context_id' => self::CONTEXT_ID,
            'setting_name' => 'customTitle_trending',
            'setting_value' => json_encode(['en_US' => 'Hot now', 'pt_BR' => 'Em alta']),
            'setting_type' => 'object',
        ]);

        (new InstallMigration())->up();

        $value = DB::table('plugin_settings')
            ->where('plugin_name', 'fgvopenrankplugin')
            ->where('context_id', self::CONTEXT_ID)
            ->where('setting_name', 'customTitle_trending')
            ->value('setting_value');
        $this->assertSame(['en' => 'Hot now', 'pt_BR' => 'Em alta'], json_decode($value, true));
    }
}
