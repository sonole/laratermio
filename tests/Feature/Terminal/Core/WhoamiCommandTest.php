<?php

use App\Enums\SettingKey;
use App\Terminal\Commands\WhoamiCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('whoami', function () {
    it('is named whoami and listed in the system help group', function () {
        $command = new WhoamiCommand;

        expect($command->name())->toBe('whoami')
            ->and($command->helpGroup())->toBe('system');
    });

    it('prints the default visitor name when no prompt username is set', function () {
        $response = terminalCoreRun('whoami');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('<span class="t-accent">visitor</span>');
    });

    it('prints the prompt username from the settings', function () {
        terminalCoreSetting(SettingKey::PromptUsername, 'alex');

        expect(terminalCoreHtml('whoami'))->toContain('<span class="t-accent">alex</span>');
    });

    it('escapes the username', function () {
        terminalCoreSetting(SettingKey::PromptUsername, '<script>x</script>');

        $html = terminalCoreHtml('whoami');

        expect($html)->toContain('&lt;script&gt;x&lt;/script&gt;')->not->toContain('<script>');
    });

    it('rejects any argument', function () {
        expect(terminalCoreHtml('whoami', 'root'))->toContain('unknown option: <strong>root</strong>');
    });

    it('escapes the rejected argument', function () {
        $html = terminalCoreHtml('whoami', '<b>root</b>');

        expect($html)->toContain('&lt;b&gt;root&lt;/b&gt;')->not->toContain('<b>root');
    });

    it('answers --help with its description', function () {
        expect(terminalCoreHtml('whoami', '--help'))
            ->toContain('// whoami --help')
            ->toContain('Print current user identity');
    });
});
