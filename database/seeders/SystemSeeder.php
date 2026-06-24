<?php

namespace Database\Seeders;

use App\Enums\NavItemType;
use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Models\NavItem;
use App\Models\Setting;
use App\Models\TerminalCommand;
use App\Models\User;
use App\Terminal\Commands\AboutCommand;
use App\Terminal\Commands\CdCommand;
use App\Terminal\Commands\ClearCommand;
use App\Terminal\Commands\CMatrixCommand;
use App\Terminal\Commands\ContactCommand;
use App\Terminal\Commands\EducationCommand;
use App\Terminal\Commands\ExperienceCommand;
use App\Terminal\Commands\FastfetchCommand;
use App\Terminal\Commands\HelpCommand;
use App\Terminal\Commands\HistoryCommand;
use App\Terminal\Commands\LsCommand;
use App\Terminal\Commands\OpenCommand;
use App\Terminal\Commands\PingCommand;
use App\Terminal\Commands\ProjectsCommand;
use App\Terminal\Commands\SearchCommand;
use App\Terminal\Commands\SkillsCommand;
use App\Terminal\Commands\SudoCommand;
use App\Terminal\Commands\ThemeCommand;
use App\Terminal\Commands\WhoamiCommand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedTerminalCommands();
        $this->seedNavItems();
        $this->seedSettings();
    }

    private function seedAdmin(): void
    {
        $email = config('app.admin.email');
        $name = config('app.admin.name');

        if (empty($email) || empty($name)) {
            $this->command->warn('Admin email and name are not set in the .env file. Skipping admin seeding.');

            return;
        }

        if (User::query()->where('email', $email)->doesntExist()) {
            User::query()->create([
                'email' => $email,
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'must_change_password' => true,
            ]);
        }
    }

    private function seedTerminalCommands(): void
    {
        foreach ($this->commands() as $data) {
            TerminalCommand::query()->updateOrCreate(
                [
                    'name' => $data['name'],
                ],
                [
                    'name' => $data['name'],
                    'command_class' => $data['command_class'],
                    'display_label' => $data['display_label'],
                    'description' => $data['description'] ?? null,
                    'is_enabled' => true,
                    'interaction_type' => $data['interaction_type'],
                ]
            );
        }
    }

    private function seedNavItems(): void
    {
        $commands = TerminalCommand::query()
            ->whereIn('command_class', [
                AboutCommand::class,
                EducationCommand::class,
                ExperienceCommand::class,
                ProjectsCommand::class,
                SkillsCommand::class,
                ContactCommand::class,
            ])
            ->orderBy('id')
            ->get();

        $sort = 0;

        foreach ($commands as $command) {
            $commandArgs = match ($command->command_class) {
                ExperienceCommand::class, ProjectsCommand::class => '-a',
                default => null,
            };
            NavItem::query()->updateOrCreate(
                [
                    'terminal_command_id' => $command->id,
                ],
                [
                    'command_args' => $commandArgs,
                    'type' => NavItemType::Command,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]
            );
        }

        NavItem::query()->updateOrCreate(
            ['type' => NavItemType::Cv],
            [
                'label' => 'cv',
                'target' => '_blank',
                'sort_order' => $sort,
                'is_active' => true,
            ]
        );
    }

    private function seedSettings(): void
    {
        foreach ($this->settingDefinitions() as $def) {
            $record = Setting::query()->firstOrCreate(
                ['key' => $def['key']],
                array_merge($def, ['value' => null])
            );

            if (! $record->wasRecentlyCreated) {
                $record->update([
                    'group' => $def['group'],
                    'label' => $def['label'],
                    'type' => $def['type'],
                    'sort_order' => $def['sort_order'],
                ]);
            }
        }
    }

    /** @return array<int, array{group: string, key: string, label: string, type: SettingType, sort_order: int}> */
    private function settingDefinitions(): array
    {
        return [
            ['group' => 'Identity', 'key' => SettingKey::Name->value, 'label' => 'Name', 'type' => SettingType::String, 'sort_order' => 0],
            ['group' => 'Identity', 'key' => SettingKey::Role->value, 'label' => 'Role', 'type' => SettingType::String, 'sort_order' => 1],
            ['group' => 'Identity', 'key' => SettingKey::About->value, 'label' => 'About text', 'type' => SettingType::Text, 'sort_order' => 2],
            ['group' => 'Identity', 'key' => SettingKey::AsciiArtEnabled->value, 'label' => 'Show ASCII art', 'type' => SettingType::Switch, 'sort_order' => 3],
            ['group' => 'Identity', 'key' => SettingKey::AsciiArt->value, 'label' => 'ASCII art', 'type' => SettingType::File, 'sort_order' => 4],
            ['group' => 'Identity', 'key' => SettingKey::AsciiArtSize->value, 'label' => 'ASCII art size (em)', 'type' => SettingType::Number, 'sort_order' => 5],
            ['group' => 'Identity', 'key' => SettingKey::AsciiArtColor->value, 'label' => 'ASCII art color', 'type' => SettingType::Color, 'sort_order' => 6],
            ['group' => 'Terminal Prompt', 'key' => SettingKey::PromptUsername->value, 'label' => 'Username', 'type' => SettingType::String, 'sort_order' => 7],
            ['group' => 'Terminal Prompt', 'key' => SettingKey::PromptUsernameColor->value, 'label' => 'Username color', 'type' => SettingType::Color, 'sort_order' => 8],
            ['group' => 'Terminal Prompt', 'key' => SettingKey::PromptHostname->value, 'label' => 'Hostname', 'type' => SettingType::String, 'sort_order' => 9],
            ['group' => 'Terminal Prompt', 'key' => SettingKey::PromptHostnameColor->value, 'label' => 'Hostname color', 'type' => SettingType::Color, 'sort_order' => 10],
            ['group' => 'Terminal Prompt', 'key' => SettingKey::PromptSeparatorColor->value, 'label' => 'Separator color', 'type' => SettingType::Color, 'sort_order' => 11],
            ['group' => 'SEO', 'key' => SettingKey::SeoTitle->value, 'label' => 'Page title', 'type' => SettingType::String, 'sort_order' => 13],
            ['group' => 'SEO', 'key' => SettingKey::SeoDescription->value, 'label' => 'Meta description', 'type' => SettingType::Text, 'sort_order' => 14],
            ['group' => 'SEO', 'key' => SettingKey::Favicon->value, 'label' => 'Favicon', 'type' => SettingType::File, 'sort_order' => 15],
            ['group' => 'SEO', 'key' => SettingKey::SeoOgImage->value, 'label' => 'Social share image', 'type' => SettingType::File, 'sort_order' => 16],
            ['group' => 'SEO', 'key' => SettingKey::SeoTwitterHandle->value, 'label' => 'Twitter / X handle', 'type' => SettingType::String, 'sort_order' => 17],
        ];
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     command_class: string,
     *     display_label: string,
     *     description: string|null,
     *     interaction_type: string|null
     * }>
     */
    private function commands(): array
    {
        return [
            [
                'name' => 'about',
                'command_class' => AboutCommand::class,
                'display_label' => 'about',
                'description' => 'Who I am and what drives me',
                'interaction_type' => null,
            ],
            [
                'name' => 'education',
                'command_class' => EducationCommand::class,
                'display_label' => 'education',
                'description' => 'Education & certifications',
                'interaction_type' => null,
            ],
            [
                'name' => 'experience',
                'command_class' => ExperienceCommand::class,
                'display_label' => 'experience',
                'description' => 'Work history',
                'interaction_type' => 'paginate',
            ],
            [
                'name' => 'projects',
                'command_class' => ProjectsCommand::class,
                'display_label' => 'projects',
                'description' => 'Side projects and open source',
                'interaction_type' => 'selector',
            ],
            [
                'name' => 'skills',
                'command_class' => SkillsCommand::class,
                'display_label' => 'skills',
                'description' => 'Technical skills and stack',
                'interaction_type' => null,
            ],
            [
                'name' => 'contact',
                'command_class' => ContactCommand::class,
                'display_label' => 'contact',
                'description' => 'Get in touch',
                'interaction_type' => null,
            ],
            [
                'name' => 'help',
                'command_class' => HelpCommand::class,
                'display_label' => 'help',
                'description' => 'Show available commands',
                'interaction_type' => null,
            ],
            [
                'name' => 'whoami',
                'command_class' => WhoamiCommand::class,
                'display_label' => 'whoami',
                'description' => 'Print current user identity',
                'interaction_type' => null,
            ],
            [
                'name' => 'clear',
                'command_class' => ClearCommand::class,
                'display_label' => 'clear',
                'description' => 'Clear the terminal screen',
                'interaction_type' => null,
            ],
            [
                'name' => 'open',
                'command_class' => OpenCommand::class,
                'display_label' => 'open <name>',
                'description' => 'Open a link in a new tab',
                'interaction_type' => null,
            ],
            [
                'name' => 'theme',
                'command_class' => ThemeCommand::class,
                'display_label' => 'theme <mode>',
                'description' => 'Switch color scheme (light / dark / system)',
                'interaction_type' => null,
            ],
            [
                'name' => 'search',
                'command_class' => SearchCommand::class,
                'display_label' => 'search <query>',
                'description' => 'Search across experience, projects and skills',
                'interaction_type' => null,
            ],
            [
                'name' => 'fastfetch',
                'command_class' => FastfetchCommand::class,
                'display_label' => 'fastfetch',
                'description' => 'Display system information',
                'interaction_type' => null,
            ],
            [
                'name' => 'ping',
                'command_class' => PingCommand::class,
                'display_label' => 'ping',
                'description' => 'Measure connection latency to the server',
                'interaction_type' => null,
            ],
            [
                'name' => 'cmatrix',
                'command_class' => CMatrixCommand::class,
                'display_label' => 'cmatrix',
                'description' => 'Enter the Matrix',
                'interaction_type' => null,
            ],
            [
                'name' => 'sudo',
                'command_class' => SudoCommand::class,
                'display_label' => 'sudo',
                'description' => 'Run a command as root',
                'interaction_type' => null,
            ],
            [
                'name' => 'history',
                'command_class' => HistoryCommand::class,
                'display_label' => 'history',
                'description' => 'Show command history',
                'interaction_type' => null,
            ],
            [
                'name' => 'cd',
                'command_class' => CdCommand::class,
                'display_label' => 'cd <dir>',
                'description' => 'Navigate the portfolio filesystem',
                'interaction_type' => null,
            ],
            [
                'name' => 'ls',
                'command_class' => LsCommand::class,
                'display_label' => 'ls',
                'description' => 'List contents of current directory',
                'interaction_type' => null,
            ],
        ];
    }
}
