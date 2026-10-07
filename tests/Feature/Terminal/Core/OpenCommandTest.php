<?php

use App\Models\ContactItem;
use App\Terminal\Commands\OpenCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('open', function () {
    it('is named open and listed in the explore help group', function () {
        $command = new OpenCommand;

        expect($command->name())->toBe('open')
            ->and($command->helpGroup())->toBe('explore');
    });

    it('answers --help with its description', function () {
        expect(terminalCoreHtml('open', '--help'))
            ->toContain('// open --help')
            ->toContain('Open a link in a new tab');
    });
});

describe('open without an argument', function () {
    it('lists the active contact items that have a link', function () {
        ContactItem::factory()->create(['label' => 'My GitHub', 'icon' => 'fa-brands fa-github', 'url' => 'https://github.com/me', 'sort_order' => 1]);
        ContactItem::factory()->create(['label' => 'Email me', 'icon' => 'fa-solid fa-envelope', 'url' => 'mailto:me@example.com', 'sort_order' => 2]);

        $response = terminalCoreRun('open');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('<p class="t-header">// open</p>')
            ->and($response['html'])->toContain('<span class="t-link">My GitHub</span>')
            ->and($response['html'])->toContain('<span class="t-link">Email me</span>')
            ->and($response['html'])->toContain('Usage: open &lt;alias or label&gt;');
    });

    it('hints at the alias that opens each item', function () {
        ContactItem::factory()->create(['icon' => 'fa-brands fa-github', 'label' => 'Code', 'url' => 'https://github.com/me']);

        expect(terminalCoreHtml('open'))->toContain('<span class="t-dim"> (open github)</span>');
    });

    it('shows font awesome icons as icon tags and anything else as text', function () {
        ContactItem::factory()->create(['icon' => 'fa-brands fa-github', 'label' => 'Code', 'url' => 'https://github.com/me', 'sort_order' => 1]);
        ContactItem::factory()->create(['icon' => '@', 'label' => 'Handle', 'url' => 'https://example.com', 'sort_order' => 2]);

        $html = terminalCoreHtml('open');

        expect($html)
            ->toContain('<i class="fa-brands fa-github fa-fw t-dim"></i>')
            ->toContain('<span class="t-dim">@</span>');
    });

    it('leaves out inactive items and items without a link', function () {
        ContactItem::factory()->create(['label' => 'Shown', 'url' => 'https://example.com']);
        ContactItem::factory()->inactive()->create(['label' => 'Inactive item', 'url' => 'https://example.com']);
        ContactItem::factory()->create(['label' => 'No link item', 'url' => null]);
        ContactItem::factory()->create(['label' => 'Empty link item', 'url' => '']);

        $html = terminalCoreHtml('open');

        expect($html)
            ->toContain('Shown')
            ->not->toContain('Inactive item')
            ->not->toContain('No link item')
            ->not->toContain('Empty link item');
    });

    it('lists the items in their sort order', function () {
        ContactItem::factory()->create(['label' => 'Zulu', 'sort_order' => 2]);
        ContactItem::factory()->create(['label' => 'Alpha', 'sort_order' => 1]);

        $html = terminalCoreHtml('open');

        expect(strpos($html, 'Alpha'))->toBeLessThan(strpos($html, 'Zulu'));
    });

    it('escapes labels and icons', function () {
        ContactItem::factory()->create(['label' => '<b>Bold</b>', 'icon' => '<i>x</i>', 'url' => 'https://example.com']);

        $html = terminalCoreHtml('open');

        expect($html)->toContain('&lt;b&gt;Bold&lt;/b&gt;')->toContain('&lt;i&gt;x&lt;/i&gt;')->not->toContain('<b>Bold');
    });

    it('still renders when there are no links', function () {
        expect(terminalCoreHtml('open'))->toContain('// open</p>')->toContain('t-help-rows');
    });
});

describe('open with an argument', function () {
    it('opens the link of the item whose label matches', function () {
        ContactItem::factory()->create(['label' => 'My Portfolio Site', 'icon' => 'fa-solid fa-globe', 'url' => 'https://example.com']);

        expect(terminalCoreRun('open', 'portfolio'))->toBe(['type' => 'open', 'url' => 'https://example.com']);
    });

    it('matches case-insensitively and ignores surrounding whitespace', function () {
        ContactItem::factory()->create(['label' => 'My Portfolio Site', 'url' => 'https://example.com']);

        expect(terminalCoreRun('open', '  PORTFOLIO  '))->toBe(['type' => 'open', 'url' => 'https://example.com']);
    });

    it('matches on the icon alias, not just the label', function () {
        ContactItem::factory()->create(['label' => 'Code', 'icon' => 'fa-brands fa-github', 'url' => 'https://github.com/me']);

        expect(terminalCoreRun('open', 'github'))->toBe(['type' => 'open', 'url' => 'https://github.com/me']);
    });

    it('opens the first match in sort order when several items match', function () {
        ContactItem::factory()->create(['label' => 'Blog two', 'url' => 'https://two.test', 'sort_order' => 2]);
        ContactItem::factory()->create(['label' => 'Blog one', 'url' => 'https://one.test', 'sort_order' => 1]);

        expect(terminalCoreRun('open', 'blog')['url'])->toBe('https://one.test');
    });

    it('ignores items without a link and inactive items', function () {
        ContactItem::factory()->create(['label' => 'Blog nolink', 'url' => null, 'sort_order' => 1]);
        ContactItem::factory()->inactive()->create(['label' => 'Blog inactive', 'url' => 'https://inactive.test', 'sort_order' => 2]);

        $response = terminalCoreRun('open', 'blog');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('No link found matching');
    });

    it('reports when nothing matches and points at the list', function () {
        ContactItem::factory()->create(['label' => 'Email me', 'url' => 'mailto:me@example.com']);

        $response = terminalCoreRun('open', 'carrier-pigeon');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('No link found matching <strong>carrier-pigeon</strong>')
            ->and($response['html'])->toContain('<span class=\'t-accent\'>open</span>');
    });

    it('escapes what was typed in the no-match message', function () {
        $html = terminalCoreHtml('open', '<script>alert(1)</script>');

        expect($html)
            ->toContain('<strong>&lt;script&gt;alert(1)&lt;/script&gt;</strong>')
            ->not->toContain('<script>');
    });

    it('opens the admin panel for "admin" when no contact item matches', function () {
        expect(terminalCoreRun('open', 'admin'))->toBe([
            'type' => 'open',
            'url' => route('filament.admin.pages.dashboard'),
        ]);
    });

    it('prefers a contact item over the admin shortcut', function () {
        ContactItem::factory()->create(['label' => 'Admin blog', 'url' => 'https://blog.test']);

        expect(terminalCoreRun('open', 'admin'))->toBe(['type' => 'open', 'url' => 'https://blog.test']);
    });
});
