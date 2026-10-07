<?php

namespace APP\plugins\generic\fgvOpenRank\classes\migrations;

use Illuminate\Database\Migrations\Migration;

class InstallMigration extends Migration
{
    public function up(): void
    {
        (new PluginRenameMigration())->up();
        (new LegacySettingsMigration())->up();
    }

    public function down(): void
    {
    }
}
