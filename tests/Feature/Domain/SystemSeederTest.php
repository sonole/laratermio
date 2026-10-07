<?php

use App\Enums\CvFont;
use App\Enums\InteractionType;
use App\Enums\NavItemType;
use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Models\Experience;
use App\Models\NavItem;
use App\Models\Setting;
use App\Models\TerminalCommand;
use App\Models\User;
use App\Terminal\Commands\BaseCommand;
use App\Terminal\Commands\ExperienceCommand;
use App\Terminal\Commands\ProjectsCommand;
use App\Terminal\Contracts\TerminalCommandContract;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

describe('terminal commands', function () {
    it('registers every shipped command', function () {
        seedSystem();

        expect(TerminalCommand::query()->orderBy('name')->pluck('name')->all())->toBe([
            'about', 'cd', 'clear', 'cmatrix', 'contact', 'education', 'experience', 'fastfetch', 'help',
            'history', 'ls', 'open', 'ping', 'projects', 'search', 'skills', 'sudo', 'theme', 'whoami',
        ]);
    });

    it('points every row at an existing command class with a matching name', function () {
        seedSystem();

        foreach (TerminalCommand::query()->get() as $record) {
            expect(class_exists($record->command_class))->toBeTrue("{$record->name} points at a missing class")
                ->and(app($record->command_class))->toBeInstanceOf(TerminalCommandContract::class)
                ->and(app($record->command_class)->name())->toBe($record->name);
        }
    });

    it('registers every command class that exists in the app', function () {
        seedSystem();

        $classes = collect(glob(app_path('Terminal/Commands/*Command.php')))
            ->map(fn (string $file) => 'App\\Terminal\\Commands\\'.basename($file, '.php'))
            ->reject(fn (string $class) => $class === BaseCommand::class)
            ->values();

        expect($classes)->not->toBeEmpty()
            ->and(TerminalCommand::query()->pluck('command_class')->all())->toEqualCanonicalizing($classes->all());
    });

    it('activates every command and gives it a label and description', function () {
        seedSystem();

        foreach (TerminalCommand::query()->get() as $record) {
            expect($record->is_active)->toBeTrue()
                ->and($record->display_label)->not->toBeEmpty()
                ->and($record->description)->not->toBeEmpty();
        }
    });

    it('sets the interaction type of the paginated and selectable commands only', function () {
        seedSystem();

        $types = TerminalCommand::query()->pluck('interaction_type', 'name');

        expect($types['experience'])->toBe(InteractionType::Paginate)
            ->and($types['projects'])->toBe(InteractionType::Selector)
            ->and($types->except(['experience', 'projects'])->filter()->all())->toBeEmpty();
    });
});

describe('nav items', function () {
    it('creates a command button for each content command, in order', function () {
        seedSystem();

        $items = NavItem::query()->where('type', NavItemType::Command)->ordered()->with('terminalCommand')->get();

        expect($items->map(fn (NavItem $item) => $item->terminalCommand->name)->all())
            ->toBe(['about', 'education', 'experience', 'projects', 'skills', 'contact'])
            ->and($items->pluck('sort_order')->all())->toBe([0, 1, 2, 3, 4, 5])
            ->and($items->every(fn (NavItem $item) => $item->is_active))->toBeTrue();
    });

    it('opens experience and projects with the -a argument only', function () {
        seedSystem();

        $args = NavItem::query()->with('terminalCommand')->get()
            ->filter(fn (NavItem $item) => $item->terminalCommand !== null)
            ->mapWithKeys(fn (NavItem $item) => [$item->terminalCommand->command_class => $item->command_args]);

        expect($args[ExperienceCommand::class])->toBe('-a')
            ->and($args[ProjectsCommand::class])->toBe('-a')
            ->and($args->except([ExperienceCommand::class, ProjectsCommand::class])->filter()->all())->toBeEmpty();
    });

    it('adds a CV button last that opens in a new tab', function () {
        seedSystem();

        $cv = NavItem::query()->where('type', NavItemType::Cv)->sole();

        expect($cv->label)->toBe('cv')
            ->and($cv->target)->toBe('_blank')
            ->and($cv->terminal_command_id)->toBeNull()
            ->and($cv->sort_order)->toBe(NavItem::query()->max('sort_order'))
            ->and($cv->getDisplayLabel())->toBe('cv');
    });
});

describe('settings', function () {
    it('creates a row for every setting key', function () {
        seedSystem();

        $stored = Setting::query()->pluck('key')->all();

        foreach (SettingKey::cases() as $key) {
            expect($stored)->toContain($key->value);
        }
    });

    it('creates the CV settings', function () {
        seedSystem();

        $settings = Setting::query()->where('group', 'CV')->get()->keyBy('key');

        expect($settings->keys()->all())->toEqualCanonicalizing([
            'cv_font', 'cv_locales', 'cv_title_objective', 'cv_title_experience', 'cv_title_education', 'cv_title_skills', 'cv_title_projects',
        ])
            ->and($settings['cv_font']->type)->toBe(SettingType::Select)
            ->and($settings['cv_font']->value)->toBe(CvFont::default()->value)
            ->and($settings['cv_locales']->type)->toBe(SettingType::MultiSelect)
            ->and($settings['cv_locales']->value)->toBeNull()
            ->and($settings['cv_title_skills']->type)->toBe(SettingType::String);
    });

    it('assigns the right input type to the identity, prompt and SEO settings', function () {
        seedSystem();

        $types = Setting::query()->pluck('type', 'key');

        expect($types['name'])->toBe(SettingType::String)
            ->and($types['about'])->toBe(SettingType::Text)
            ->and($types['ascii_art_enabled'])->toBe(SettingType::Switch)
            ->and($types['ascii_art'])->toBe(SettingType::File)
            ->and($types['ascii_art_size'])->toBe(SettingType::Number)
            ->and($types['prompt_username_color'])->toBe(SettingType::Color)
            ->and($types['favicon'])->toBe(SettingType::File)
            ->and($types['seo_description'])->toBe(SettingType::Text);
    });

    it('gives every setting a unique sort order', function () {
        seedSystem();

        $orders = Setting::query()->pluck('sort_order')->all();

        expect($orders)->toBe(array_unique($orders));
    });

    it('seeds no content values, so the app uses its built-in defaults', function () {
        seedSystem();

        expect(Setting::query()->where('key', 'name')->value('value'))->toBeNull()
            ->and(Setting::query()->where('key', 'about')->value('value'))->toBeNull();
    });
});

describe('admin user', function () {
    it('creates the admin from the app config and forces a password change', function () {
        config(['app.admin.email' => 'admin@example.test', 'app.admin.name' => 'Site Admin']);

        seedSystem();

        $admin = User::query()->where('email', 'admin@example.test')->sole();

        expect($admin->name)->toBe('Site Admin')
            ->and($admin->must_change_password)->toBeTrue()
            ->and($admin->email_verified_at)->not->toBeNull()
            ->and(Hash::check('password', $admin->password))->toBeTrue();
    });

    it('does not duplicate or reset an existing admin', function () {
        config(['app.admin.email' => 'admin@example.test', 'app.admin.name' => 'Site Admin']);
        User::factory()->create(['email' => 'admin@example.test', 'name' => 'Renamed', 'must_change_password' => false]);

        seedSystem();

        $admin = User::query()->where('email', 'admin@example.test')->sole();

        expect(User::query()->count())->toBe(1)
            ->and($admin->name)->toBe('Renamed')
            ->and($admin->must_change_password)->toBeFalse();
    });

    it('skips the admin when the email or name is not configured', function (?string $email, ?string $name) {
        config(['app.admin.email' => $email, 'app.admin.name' => $name]);

        seedSystem();

        expect(User::query()->count())->toBe(0)
            ->and(TerminalCommand::query()->count())->toBeGreaterThan(0);
    })->with([
        [null, 'Site Admin'],
        ['admin@example.test', null],
        ['', ''],
    ]);
});

describe('running twice', function () {
    it('does not duplicate any row', function () {
        seedSystem();
        $counts = [TerminalCommand::query()->count(), NavItem::query()->count(), Setting::query()->count(), User::query()->count()];
        $ids = TerminalCommand::query()->orderBy('id')->pluck('id')->all();

        seedSystem();

        expect([TerminalCommand::query()->count(), NavItem::query()->count(), Setting::query()->count(), User::query()->count()])->toBe($counts)
            ->and(TerminalCommand::query()->orderBy('id')->pluck('id')->all())->toBe($ids)
            ->and(NavItem::query()->where('type', NavItemType::Cv)->count())->toBe(1);
    });

    it('keeps the values the admin entered in settings', function () {
        seedSystem();
        Setting::query()->where('key', 'name')->update(['value' => 'Alex Example']);
        Setting::query()->where('key', 'cv_font')->update(['value' => 'times']);

        seedSystem();

        expect(Setting::query()->where('key', 'name')->value('value'))->toBe('Alex Example')
            ->and(Setting::query()->where('key', 'cv_font')->value('value'))->toBe('times');
    });

    it('repairs the definition of a setting the admin cannot edit', function () {
        seedSystem();
        Setting::query()->where('key', 'name')->update(['label' => 'Changed', 'type' => 'color', 'group' => 'Other', 'sort_order' => 99]);

        seedSystem();

        $name = Setting::query()->where('key', 'name')->sole();

        expect($name->label)->toBe('Name')
            ->and($name->type)->toBe(SettingType::String)
            ->and($name->group)->toBe('Identity')
            ->and($name->sort_order)->toBe(0);
    });

    it('restores a deactivated or edited terminal command', function () {
        seedSystem();
        TerminalCommand::query()->where('name', 'help')->update(['is_active' => false, 'display_label' => 'edited']);

        seedSystem();

        $help = TerminalCommand::query()->where('name', 'help')->sole();

        expect($help->is_active)->toBeTrue()
            ->and($help->display_label)->toBe('help');
    });

    it('does not touch content tables', function () {
        $experience = Experience::factory()->create();

        seedSystem();
        seedSystem();

        expect(Experience::query()->pluck('id')->all())->toBe([$experience->id]);
    });
});

describe('DatabaseSeeder', function () {
    it('runs the system seeder only', function () {
        $this->seed(DatabaseSeeder::class);

        expect(TerminalCommand::query()->where('name', 'help')->exists())->toBeTrue()
            ->and(NavItem::query()->count())->toBeGreaterThan(0)
            ->and(Experience::query()->count())->toBe(0)
            ->and(Setting::query()->where('key', 'name')->exists())->toBeTrue();
    });
});
