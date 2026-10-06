<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Content tables whose text can be translated for the CV. The existing columns keep holding
     * the main language, so nothing changes for the public site or for existing data.
     *
     * @var list<string>
     */
    private array $tables = [
        'experiences',
        'educations',
        'projects',
        'skill_categories',
        'contact_items',
        'settings',
    ];

    /** @var array<string, array{label: string, type: string, sort_order: int}> */
    private array $settings = [
        'cv_locales' => ['label' => 'Additional CV languages', 'type' => 'multiselect', 'sort_order' => 19],
        'cv_title_objective' => ['label' => 'Objective title', 'type' => 'string', 'sort_order' => 20],
        'cv_title_experience' => ['label' => 'Experience title', 'type' => 'string', 'sort_order' => 21],
        'cv_title_education' => ['label' => 'Education title', 'type' => 'string', 'sort_order' => 22],
        'cv_title_skills' => ['label' => 'Skills title', 'type' => 'string', 'sort_order' => 23],
        'cv_title_projects' => ['label' => 'Projects title', 'type' => 'string', 'sort_order' => 24],
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->json('translations')->nullable();
            });
        }

        foreach ($this->settings as $key => $definition) {
            DB::table('settings')->insertOrIgnore([
                'group' => 'CV',
                'key' => $key,
                'label' => $definition['label'],
                'type' => $definition['type'],
                'value' => null,
                'sort_order' => $definition['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys($this->settings))->delete();

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('translations');
            });
        }
    }
};
