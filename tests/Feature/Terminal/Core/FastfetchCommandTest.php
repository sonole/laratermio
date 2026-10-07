<?php

use App\Enums\SettingKey;
use App\Models\Setting;
use App\Terminal\Commands\FastfetchCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('fastfetch', function () {
    it('is named fastfetch and hidden from help', function () {
        $command = new FastfetchCommand;

        expect($command->name())->toBe('fastfetch')
            ->and($command->helpGroup())->toBeNull();
    });

    it('prints the logo next to the system information', function () {
        $response = terminalCoreRun('fastfetch');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('class="t-block t-fastfetch"')
            ->and($response['html'])->toContain('<pre class="t-fastfetch-logo">')
            ->and($response['html'])->toContain('class="t-fastfetch-info"');
    });

    it('shows the prompt identity with an underline of the same length', function () {
        $html = terminalCoreHtml('fastfetch');

        expect($html)
            ->toContain('<p class="t-fastfetch-user">visitor@localhost</p>')
            ->toContain('<p class="t-dim">'.str_repeat('─', strlen('visitor@localhost')).'</p>');
    });

    it('uses the prompt username and hostname from the settings', function () {
        terminalCoreSetting(SettingKey::PromptUsername, 'alex');
        terminalCoreSetting(SettingKey::PromptHostname, 'laptop');

        expect(terminalCoreHtml('fastfetch'))->toContain('<p class="t-fastfetch-user">alex@laptop</p>');
    });

    it('escapes the identity', function () {
        terminalCoreSetting(SettingKey::PromptUsername, '<b>x</b>');

        $html = terminalCoreHtml('fastfetch');

        expect($html)->toContain('&lt;b&gt;x&lt;/b&gt;@localhost')->not->toContain('<b>x</b>');
    });

    it('lists the stack and runtime details', function () {
        $html = terminalCoreHtml('fastfetch');
        $row = fn (string $key, string $value) => '<span class="t-accent">'.$key.'</span><span class="t-dim">: </span>'.$value;

        expect($html)
            ->toContain($row('Terminal', 'laratermio'))
            ->toContain($row('PHP', PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION))
            ->toContain($row('Arch', php_uname('m')))
            ->toContain('<span class="t-accent">Framework</span><span class="t-dim">: </span>Laravel ')
            ->toContain('<span class="t-accent">Shell</span><span class="t-dim">: </span>jQuery Terminal ')
            ->toContain('<span class="t-accent">CSS</span><span class="t-dim">: </span>Tailwind ')
            ->toContain('<span class="t-accent">UI</span><span class="t-dim">: </span>Livewire ')
            ->toContain('<span class="t-accent">Admin</span><span class="t-dim">: </span>Filament ')
            ->toContain('<span class="t-accent">OPcache</span>')
            ->toContain('<span class="t-accent">Memory Usage</span><span class="t-dim">: </span>')
            ->toContain('<span class="t-accent">Uptime</span><span class="t-dim">: </span>');
    });

    it('reads the framework version from composer.lock', function () {
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true);
        $version = collect($lock['packages'])->firstWhere('name', 'laravel/framework')['version'];

        expect(terminalCoreHtml('fastfetch'))->toContain('Laravel '.ltrim($version, 'v'));
    });

    it('reports memory usage in MiB', function () {
        expect(terminalCoreHtml('fastfetch'))->toMatch('#Memory Usage</span><span class="t-dim">: </span>[\d.]+ MiB#');
    });

    it('measures uptime from the oldest setting when the host has no /proc/uptime', function () {
        Setting::query()->update(['created_at' => now()->subDays(3)]);

        expect(terminalCoreHtml('fastfetch'))->toContain('Uptime</span><span class="t-dim">: </span>3 days');
    })->skipOnLinux();

    it('says uptime is under a day for settings created today', function () {
        expect(terminalCoreHtml('fastfetch'))->toContain('Uptime</span><span class="t-dim">: </span>&lt; 1 day');
    })->skipOnLinux();

    it('formats uptime from /proc/uptime on linux hosts', function () {
        expect(terminalCoreHtml('fastfetch'))
            ->toMatch('#Uptime</span><span class="t-dim">: </span>(&lt; 1m|(\d+d ?)?(\d+h ?)?(\d+m)?)</p>#');
    })->onlyOnLinux();

    it('ignores any argument', function () {
        expect(terminalCoreHtml('fastfetch', 'anything'))->toContain('t-fastfetch-logo');
    });

    it('answers --help instead of printing the system information', function () {
        $html = terminalCoreHtml('fastfetch', '--help');

        expect($html)->toContain('// fastfetch --help')->not->toContain('t-fastfetch-logo');
    });
});
