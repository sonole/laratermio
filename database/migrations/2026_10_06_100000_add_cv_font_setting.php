<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the CV font setting to existing installs without re-running the (content-overwriting)
     * SystemSeeder. Fresh installs also get it here; the seeder keeps its definition in sync.
     */
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            'group' => 'CV',
            'key' => 'cv_font',
            'label' => 'CV font',
            'type' => 'select',
            'value' => 'helvetica',
            'sort_order' => 18,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'cv_font')->delete();
    }
};
