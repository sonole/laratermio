<?php

use App\Enums\NavItemType;
use App\Enums\SettingKey;
use App\Livewire\Terminal;
use App\Models\NavItem;
use App\Models\Project;
use App\Models\TerminalCommand;
use App\Services\CvService;
use App\Terminal\TerminalContext;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Run a command through the component, the way the JS layer does, and return what it answered.
 *
 * @return array{type: string, html?: string, url?: string, key?: string, path?: string}
 */
function webTerminalRun(Testable $component, string $command): array
{
    $answer = null;

    $component->call('execute', $command)->assertReturned(function ($returned) use (&$answer) {
        $answer = $returned;

        return true;
    });

    return $answer;
}

beforeEach(function () {
    $this->webTerminalDisk = storage_path('framework/testing/disks/web-terminal');
    config(['filesystems.disks.public.root' => $this->webTerminalDisk]);
    Storage::forgetDisk('public');
});

afterEach(function () {
    File::deleteDirectory($this->webTerminalDisk);
});

describe('mounting', function () {
    it('starts at the home directory with the navigation enabled', function () {
        Livewire::test(Terminal::class)
            ->assertOk()
            ->assertSet('cwd', '~')
            ->assertSet('navCommandsDisabled', false)
            ->assertSee('id="portfolio-terminal"', false);
    });

    it('hands the greeting and the prompt to the browser', function () {
        cvSetting(SettingKey::Name, 'Alex Example');
        cvSetting(SettingKey::Role, 'Software Engineer');
        cvSetting(SettingKey::PromptUsername, 'alex');
        cvSetting(SettingKey::PromptHostname, 'folio.dev');

        Livewire::test(Terminal::class)
            ->assertViewHas('greeting', ['name' => 'Alex Example', 'role' => 'Software Engineer'])
            ->assertViewHas('terminalPrompt', fn (string $prompt) => str_contains($prompt, '[[b;#4ade80;]alex]')
                && str_contains($prompt, '[[b;#60a5fa;]folio.dev]')
                && str_contains($prompt, ':~$'));
    });

    it('falls back to the default identity and prompt', function () {
        Livewire::test(Terminal::class)
            ->assertViewHas('greeting', ['name' => 'Dev McDevface', 'role' => 'Developer'])
            ->assertViewHas('terminalPrompt', fn (string $prompt) => str_contains($prompt, 'visitor') && str_contains($prompt, 'localhost'));
    });

    it('gives the browser a header template with a title placeholder', function () {
        Livewire::test(Terminal::class)
            ->assertViewHas('headerTemplate', '<p class="t-header">// __TITLE__</p>');
    });

    it('offers only active commands, sorted, for tab completion', function () {
        TerminalCommand::factory()->create(['name' => 'zeta']);
        TerminalCommand::factory()->create(['name' => 'alpha']);
        TerminalCommand::factory()->inactive()->create(['name' => 'hidden']);

        Livewire::test(Terminal::class)
            ->assertViewHas('commandNames', ['alpha', 'zeta']);
    });

    it('exposes the virtual filesystem roots', function () {
        Livewire::test(Terminal::class)
            ->assertViewHas('filesystemRoots', TerminalContext::FILESYSTEM_ROOTS);
    });

    it('disables the command buttons while an interactive list is open', function () {
        seedSystem();

        $enabled = Livewire::test(Terminal::class)->html();
        $disabled = Livewire::test(Terminal::class)->set('navCommandsDisabled', true)->html();

        expect(preg_match_all('/termNavExec\([^)]*\)"\s+disabled/', $enabled))->toBe(0)
            ->and(preg_match_all('/termNavExec\([^)]*\)"\s+disabled/', $disabled))->toBeGreaterThan(0);
    });
});

describe('ASCII art', function () {
    function webTerminalAscii(array $settings = [], ?string $contents = null): array
    {
        foreach ($settings as $key => $value) {
            cvSetting(SettingKey::from($key), $value);
        }

        if ($contents !== null) {
            Storage::disk('public')->put('uploads/ascii.txt', $contents);
        }

        $art = null;

        Livewire::test(Terminal::class)->assertViewHas('asciiArt', function (array $value) use (&$art) {
            $art = $value;

            return true;
        });

        return $art;
    }

    it('is empty unless it is switched on', function () {
        $art = webTerminalAscii(['ascii_art' => 'uploads/ascii.txt'], "line one\nline two");

        expect($art['lines'])->toBe([]);
    });

    it('shows the uploaded file line by line when switched on', function () {
        $art = webTerminalAscii(['ascii_art_enabled' => '1', 'ascii_art' => 'uploads/ascii.txt'], "line one\nline two");

        expect($art['lines'])->toBe(['line one', 'line two']);
    });

    it('uses the default size and colour', function () {
        $art = webTerminalAscii();

        expect($art['size'])->toBe('0.15em')
            ->and($art['color'])->toBe('#4ade80');
    });

    it('uses the configured size and colour', function () {
        $art = webTerminalAscii(['ascii_art_size' => '0.3', 'ascii_art_color' => '#ff0000']);

        expect($art['size'])->toBe('0.3em')
            ->and($art['color'])->toBe('#ff0000');
    });

    it('is empty when switched on without a file', function () {
        expect(webTerminalAscii(['ascii_art_enabled' => '1'])['lines'])->toBe([]);
    });

    it('is empty when the file is gone from disk', function () {
        $art = webTerminalAscii(['ascii_art_enabled' => '1', 'ascii_art' => 'uploads/missing.txt']);

        expect($art['lines'])->toBe([]);
    });
});

describe('running commands', function () {
    it('runs a registered command', function () {
        seedSystem();
        cvSetting(SettingKey::PromptUsername, 'alex');

        $answer = webTerminalRun(Livewire::test(Terminal::class), 'whoami');

        expect($answer['type'])->toBe('echo')
            ->and($answer['html'])->toContain('alex');
    });

    it('ignores blank input', function () {
        seedSystem();

        $component = Livewire::test(Terminal::class);

        expect(webTerminalRun($component, ''))->toBe(['type' => 'echo', 'html' => ''])
            ->and(webTerminalRun($component, "   \t "))->toBe(['type' => 'echo', 'html' => '']);
    });

    it('ignores case and surrounding whitespace', function () {
        seedSystem();

        $answer = webTerminalRun(Livewire::test(Terminal::class), '  WhoAmI  ');

        expect($answer['type'])->toBe('echo')
            ->and($answer['html'])->toContain('visitor');
    });

    it('passes everything after the command name as its argument', function () {
        seedSystem();
        Project::factory()->create(['name' => 'Laratermio', 'subtitle' => 'Terminal portfolio']);

        $answer = webTerminalRun(Livewire::test(Terminal::class), 'ls   projects');

        expect($answer['html'])->toContain('// ls ~/projects')
            ->toContain('Laratermio');
    });

    it('answers command not found for an unknown command', function () {
        seedSystem();

        $answer = webTerminalRun(Livewire::test(Terminal::class), 'frobnicate now');

        expect($answer['type'])->toBe('echo')
            ->and($answer['html'])->toContain('command not found')
            ->toContain('frobnicate now')
            ->toContain('help');
    });

    it('escapes what the visitor typed', function () {
        seedSystem();

        $answer = webTerminalRun(Livewire::test(Terminal::class), '<script>alert(1)</script>');

        expect($answer['html'])->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
            ->not->toContain('<script>');
    });

    it('does not run commands that are not registered in the database', function () {
        $answer = webTerminalRun(Livewire::test(Terminal::class), 'whoami');

        expect($answer['html'])->toContain('command not found');
    });

    it('treats an inactive command as not found', function () {
        seedSystem();
        TerminalCommand::query()->where('name', 'whoami')->update(['is_active' => false]);

        $answer = webTerminalRun(Livewire::test(Terminal::class), 'whoami');

        expect($answer['type'])->toBe('echo')
            ->and($answer['html'])->toContain('command not found')
            ->toContain('whoami');
    });

    it('treats a command whose class no longer exists as not found', function () {
        TerminalCommand::factory()->create(['name' => 'ghost', 'command_class' => 'App\\Terminal\\Commands\\GhostCommand']);

        $answer = webTerminalRun(Livewire::test(Terminal::class), 'ghost');

        expect($answer['html'])->toContain('command not found');
    });

    it('returns the typed response a command asks for', function () {
        seedSystem();

        $component = Livewire::test(Terminal::class);

        expect(webTerminalRun($component, 'clear'))->toBe(['type' => 'clear'])
            ->and(webTerminalRun($component, 'history'))->toBe(['type' => 'client_history']);
    });

    it('treats the input "0" as a command like any other', function () {
        seedSystem();

        $answer = webTerminalRun(Livewire::test(Terminal::class), '0');

        expect($answer['html'])->toContain('command not found');
    });
});

describe('working directory', function () {
    it('moves into a directory and keeps the component in sync', function () {
        seedSystem();

        $component = Livewire::test(Terminal::class);
        $answer = webTerminalRun($component, 'cd projects');

        expect($answer['type'])->toBe('cd')
            ->and($answer['path'])->toBe('~/projects')
            ->and($answer['html'])->toContain('~/projects');

        $component->assertSet('cwd', '~/projects');
    });

    it('lets later commands see the directory the visitor is in', function () {
        seedSystem();
        Project::factory()->create(['name' => 'Laratermio']);

        $component = Livewire::test(Terminal::class);
        webTerminalRun($component, 'cd projects');

        $answer = webTerminalRun($component, 'ls');

        expect($answer['html'])->toContain('// ls ~/projects')
            ->toContain('Laratermio');
    });

    it('goes back home with cd .. and cd ~', function (string $back) {
        seedSystem();

        $component = Livewire::test(Terminal::class);
        webTerminalRun($component, 'cd skills');
        $component->assertSet('cwd', '~/skills');

        $answer = webTerminalRun($component, $back);

        expect($answer['type'])->toBe('cd')
            ->and($answer['path'])->toBe('~');

        $component->assertSet('cwd', '~');
    })->with(['cd ..', 'cd ~', 'cd']);

    it('stays where it is when the directory does not exist', function () {
        seedSystem();

        $component = Livewire::test(Terminal::class);
        webTerminalRun($component, 'cd education');
        $answer = webTerminalRun($component, 'cd nowhere');

        expect($answer['type'])->toBe('echo')
            ->and($answer['html'])->toContain('no such directory');

        $component->assertSet('cwd', '~/education');
    });

    it('lists the home directory when the component starts at home', function () {
        seedSystem();

        $answer = webTerminalRun(Livewire::test(Terminal::class), 'ls');

        expect($answer['html'])->toContain('// ls ~/')
            ->toContain('projects/');
    });

    it('does not leak the directory from one visitor to the next', function () {
        seedSystem();

        webTerminalRun(Livewire::test(Terminal::class), 'cd projects');

        $answer = webTerminalRun(Livewire::test(Terminal::class), 'ls');

        expect($answer['html'])->toContain('// ls ~/')
            ->not->toContain('// ls ~/projects');
    });

    it('does not trust a tampered directory', function () {
        seedSystem();

        $component = Livewire::test(Terminal::class)->set('cwd', '~/../../etc');

        expect(webTerminalRun($component, 'ls')['html'])->toContain('no such directory');
    });
});

describe('structured data', function () {
    it('returns the numbered items of a paginated or selectable command', function () {
        seedSystem();
        Project::factory()->create(['name' => 'Second', 'subtitle' => 'two', 'sort_order' => 2]);
        Project::factory()->create(['name' => 'First', 'subtitle' => 'one', 'sort_order' => 1]);
        Project::factory()->inactive()->create(['name' => 'Hidden']);

        $items = [];

        Livewire::test(Terminal::class)
            ->call('getStructuredData', 'projects')
            ->assertReturned(function ($returned) use (&$items) {
                $items = $returned;

                return true;
            });

        expect($items)->toHaveCount(2)
            ->and($items[0])->toMatchArray(['n' => 1, 'name' => 'First', 'subtitle' => 'one'])
            ->and($items[0]['html'])->toContain('First')
            ->and($items[1])->toMatchArray(['n' => 2, 'name' => 'Second']);
    });

    it('returns nothing for commands without structured data, unknown or inactive ones', function (string $type) {
        seedSystem();
        TerminalCommand::query()->where('name', 'skills')->update(['is_active' => false]);

        Livewire::test(Terminal::class)
            ->call('getStructuredData', $type)
            ->assertReturned([]);
    })->with(['whoami', 'nope', 'skills']);
});

describe('navigation items', function () {
    it('maps command nav items to the command to run and its label', function () {
        $experience = TerminalCommand::factory()->create(['name' => 'experience', 'display_label' => 'my work']);
        $about = TerminalCommand::factory()->create(['name' => 'about', 'display_label' => 'about me']);
        $hidden = TerminalCommand::factory()->create(['name' => 'hidden', 'display_label' => 'hidden']);

        NavItem::factory()->create(['type' => NavItemType::Command, 'terminal_command_id' => $experience->id, 'command_args' => '-a', 'sort_order' => 2]);
        NavItem::factory()->create(['type' => NavItemType::Command, 'terminal_command_id' => $about->id, 'sort_order' => 1]);
        NavItem::factory()->inactive()->create(['type' => NavItemType::Command, 'terminal_command_id' => $hidden->id]);
        NavItem::factory()->create(['label' => 'a link', 'url' => 'https://example.com']);

        expect(Livewire::test(Terminal::class)->instance()->navCommandItems())->toBe([
            ['exec' => 'about', 'label' => 'about me'],
            ['exec' => 'experience -a', 'label' => 'my work'],
        ]);
    });

    it('maps link nav items to their url, target and label', function () {
        NavItem::factory()->create(['label' => 'Blog', 'url' => 'https://blog.example.com', 'target' => '_self', 'sort_order' => 2]);
        NavItem::factory()->create(['label' => 'Code', 'url' => 'https://code.example.com', 'target' => '_blank', 'sort_order' => 1]);
        NavItem::factory()->inactive()->create(['label' => 'Gone', 'url' => 'https://gone.example.com']);

        expect(Livewire::test(Terminal::class)->instance()->navLinkItems())->toBe([
            ['url' => 'https://code.example.com', 'target' => '_blank', 'label' => 'Code'],
            ['url' => 'https://blog.example.com', 'target' => '_self', 'label' => 'Blog'],
        ]);
    });

    it('only offers the CV button once a CV exists, pointing at the CV route', function () {
        NavItem::factory()->create(['type' => NavItemType::Cv, 'label' => 'resume', 'url' => null, 'target' => '_blank']);

        expect(Livewire::test(Terminal::class)->instance()->navLinkItems())->toBe([]);

        Storage::disk('public')->put(CvService::PATH, 'pdf');

        expect(Livewire::test(Terminal::class)->instance()->navLinkItems())->toBe([
            ['url' => route('cv'), 'target' => '_blank', 'label' => 'resume'],
        ]);
    });

    it('labels an unnamed CV button "cv"', function () {
        NavItem::factory()->create(['type' => NavItemType::Cv, 'label' => null, 'url' => null]);
        Storage::disk('public')->put(CvService::PATH, 'pdf');

        expect(Livewire::test(Terminal::class)->instance()->navLinkItems()[0]['label'])->toBe('cv');
    });
});
