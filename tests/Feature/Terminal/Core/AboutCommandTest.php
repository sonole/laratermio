<?php

use App\Enums\SettingKey;
use App\Terminal\Commands\AboutCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('about', function () {
    it('is named about and listed in the explore help group', function () {
        $command = new AboutCommand;

        expect($command->name())->toBe('about')
            ->and($command->helpGroup())->toBe('explore');
    });

    it('prints the about text under the name and role', function () {
        terminalCoreSetting(SettingKey::Name, 'Alex Example');
        terminalCoreSetting(SettingKey::Role, 'Software Engineer');
        terminalCoreSetting(SettingKey::About, 'Experienced developer focused on Laravel.');

        $response = terminalCoreRun('about');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('<p class="t-header">// about</p>')
            ->and($response['html'])->toContain('Experienced developer focused on Laravel.')
            ->and($response['html'])->toContain('<p class="t-dim t-mt">Alex Example &mdash; Software Engineer</p>');
    });

    it('falls back to the default name and role', function () {
        terminalCoreSetting(SettingKey::About, 'Some text.');

        expect(terminalCoreHtml('about'))->toContain('Dev McDevface &mdash; Developer');
    });

    it('wraps the text in a well-formed paragraph', function () {
        terminalCoreSetting(SettingKey::About, 'Some text.');

        expect(terminalCoreHtml('about'))->toContain('<p class="t-paragraph">Some text.</p>');
    });

    it('escapes the about text, name and role', function () {
        terminalCoreSetting(SettingKey::Name, '<b>Name</b>');
        terminalCoreSetting(SettingKey::Role, '<i>Role</i>');
        terminalCoreSetting(SettingKey::About, '<script>alert(1)</script>');

        $html = terminalCoreHtml('about');

        expect($html)
            ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
            ->toContain('&lt;b&gt;Name&lt;/b&gt;')
            ->toContain('&lt;i&gt;Role&lt;/i&gt;')
            ->not->toContain('<script>')
            ->not->toContain('<b>Name');
    });

    it('reports a missing about text', function () {
        $response = terminalCoreRun('about');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain("<span class='t-error'>about text not found.</span>");
    });

    it('treats a blank about text as missing', function () {
        terminalCoreSetting(SettingKey::About, '   ');

        expect(terminalCoreHtml('about'))->toContain('about text not found.');
    });

    it('rejects any argument', function () {
        terminalCoreSetting(SettingKey::About, 'Some text.');

        expect(terminalCoreHtml('about', 'me'))->toContain('unknown option: <strong>me</strong>');
    });

    it('escapes the rejected argument', function () {
        $html = terminalCoreHtml('about', '<b>me</b>');

        expect($html)->toContain('&lt;b&gt;me&lt;/b&gt;')->not->toContain('<b>me');
    });

    it('answers --help with its description', function () {
        expect(terminalCoreHtml('about', '--help'))
            ->toContain('// about --help')
            ->toContain('Who I am and what drives me');
    });
});
