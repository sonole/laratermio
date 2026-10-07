<?php

use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillCategory;
use App\Terminal\Commands\LsCommand;
use App\Terminal\TerminalContext;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('ls', function () {
    it('is named ls and listed in the system help group', function () {
        $command = new LsCommand(new TerminalContext);

        expect($command->name())->toBe('ls')
            ->and($command->helpGroup())->toBe('system');
    });

    it('lists the virtual directories from the home directory', function () {
        $response = terminalCoreRun('ls');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('<p class="t-header">// ls ~/</p>');

        foreach (TerminalContext::FILESYSTEM_ROOTS as $dir) {
            expect($response['html'])->toContain('t-ls-name">'.$dir.'/</span>');
        }
    });

    it('lists the home directory for ~ and ~/', function (string $arg) {
        expect(terminalCoreHtml('ls', $arg))->toContain('// ls ~/</p>');
    })->with(['~', '~/']);

    it('lists the current directory when called without an argument', function () {
        Project::factory()->create(['name' => 'Laratermio', 'subtitle' => 'Terminal portfolio']);
        app(TerminalContext::class)->setCwd('~/projects');

        expect(terminalCoreHtml('ls'))
            ->toContain('// ls ~/projects</p>')
            ->toContain('Laratermio')
            ->toContain('Terminal portfolio');
    });

    it('lets an explicit directory win over the current one', function () {
        SkillCategory::factory()->create(['name' => 'Backend']);
        app(TerminalContext::class)->setCwd('~/projects');

        expect(terminalCoreHtml('ls', 'skills'))->toContain('// ls ~/skills</p>')->toContain('Backend');
    });

    it('accepts the dir, ~/dir and /dir spellings', function (string $arg) {
        ContactItem::factory()->create();

        expect(terminalCoreHtml('ls', $arg))->toContain('// ls ~/contact</p>');
    })->with(['contact', '~/contact', '/contact']);

    it('rejects a directory that does not exist', function () {
        $response = terminalCoreRun('ls', 'secrets');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('ls: secrets: no such directory');
    });

    it('escapes an unknown directory in the error', function () {
        $html = terminalCoreHtml('ls', '<script>alert(1)</script>');

        expect($html)
            ->toContain('ls: &lt;script&gt;alert(1)&lt;/script&gt;: no such directory')
            ->not->toContain('<script>');
    });

    it('answers --help with its description', function () {
        expect(terminalCoreHtml('ls', '--help'))
            ->toContain('// ls --help')
            ->toContain('List contents of current directory');
    });
});

describe('ls projects', function () {
    it('lists active projects in order with their subtitles', function () {
        Project::factory()->create(['name' => 'Second', 'subtitle' => 'Sub two', 'sort_order' => 2]);
        Project::factory()->create(['name' => 'First', 'subtitle' => 'Sub one', 'sort_order' => 1]);

        $html = terminalCoreHtml('ls', 'projects');

        expect($html)
            ->toContain('// ls ~/projects</p>')
            ->toContain('Sub one')
            ->toContain('Sub two')
            ->toContain('run <span class="t-accent">projects</span> to explore')
            ->and(strpos($html, 'First'))->toBeLessThan(strpos($html, 'Second'));
    });

    it('leaves out inactive projects', function () {
        Project::factory()->create(['name' => 'Visible']);
        Project::factory()->inactive()->create(['name' => 'Hidden']);

        expect(terminalCoreHtml('ls', 'projects'))->toContain('Visible')->not->toContain('Hidden');
    });

    it('says so when there are none', function () {
        expect(terminalCoreHtml('ls', 'projects'))->toContain('no projects found.');
    });

    it('escapes project text', function () {
        Project::factory()->create(['name' => '<b>Bold</b>', 'subtitle' => '<i>x</i>']);

        $html = terminalCoreHtml('ls', 'projects');

        expect($html)->toContain('&lt;b&gt;Bold&lt;/b&gt;')->toContain('&lt;i&gt;x&lt;/i&gt;')->not->toContain('<b>Bold');
    });
});

describe('ls skills', function () {
    it('lists active categories with a preview of their first four items', function () {
        SkillCategory::factory()->create(['name' => 'Backend', 'items' => ['PHP', 'Laravel', 'MySQL', 'Redis', 'Docker']]);
        SkillCategory::factory()->inactive()->create(['name' => 'Hidden']);

        $html = terminalCoreHtml('ls', 'skills');

        expect($html)
            ->toContain('// ls ~/skills</p>')
            ->toContain('Backend')
            ->toContain('PHP, Laravel, MySQL, Redis…')
            ->not->toContain('Docker')
            ->not->toContain('Hidden');
    });

    it('says so when there are none', function () {
        expect(terminalCoreHtml('ls', 'skills'))->toContain('no skill categories found.');
    });

    it('escapes category text', function () {
        SkillCategory::factory()->create(['name' => '<u>Cat</u>', 'items' => ['<b>x</b>']]);

        $html = terminalCoreHtml('ls', 'skills');

        expect($html)->toContain('&lt;u&gt;Cat&lt;/u&gt;')->toContain('&lt;b&gt;x&lt;/b&gt;')->not->toContain('<u>Cat');
    });
});

describe('ls experience', function () {
    it('lists active entries with company and period', function () {
        $experience = Experience::factory()->create(['title' => 'Senior Developer', 'company' => 'Acme Inc']);
        Experience::factory()->inactive()->create(['title' => 'Hidden Role']);

        $html = terminalCoreHtml('ls', 'experience');

        expect($html)
            ->toContain('// ls ~/experience</p>')
            ->toContain('Senior Developer')
            ->toContain('Acme Inc &mdash; '.$experience->period)
            ->not->toContain('Hidden Role');
    });

    it('says so when there are none', function () {
        expect(terminalCoreHtml('ls', 'experience'))->toContain('no experience entries found.');
    });

    it('escapes entry text', function () {
        Experience::factory()->create(['title' => '<b>Lead</b>', 'company' => 'A & B']);

        $html = terminalCoreHtml('ls', 'experience');

        expect($html)->toContain('&lt;b&gt;Lead&lt;/b&gt;')->toContain('A &amp; B')->not->toContain('<b>Lead');
    });
});

describe('ls education', function () {
    it('lists active entries with institution and period', function () {
        $education = Education::factory()->create(['title' => 'BSc Computer Science', 'institution' => 'University of Athens']);
        Education::factory()->inactive()->create(['title' => 'Hidden Degree']);

        $html = terminalCoreHtml('ls', 'education');

        expect($html)
            ->toContain('// ls ~/education</p>')
            ->toContain('BSc Computer Science')
            ->toContain('University of Athens &mdash; '.$education->period)
            ->not->toContain('Hidden Degree');
    });

    it('says so when there are none', function () {
        expect(terminalCoreHtml('ls', 'education'))->toContain('no education entries found.');
    });

    it('escapes entry text', function () {
        Education::factory()->create(['title' => '<b>MSc</b>', 'institution' => 'A & B']);

        $html = terminalCoreHtml('ls', 'education');

        expect($html)->toContain('&lt;b&gt;MSc&lt;/b&gt;')->toContain('A &amp; B')->not->toContain('<b>MSc');
    });
});

describe('ls contact', function () {
    it('lists active contact items with their urls', function () {
        ContactItem::factory()->create(['label' => 'me@example.com', 'url' => 'mailto:me@example.com']);
        ContactItem::factory()->inactive()->create(['label' => 'hidden@example.com']);

        $html = terminalCoreHtml('ls', 'contact');

        expect($html)
            ->toContain('// ls ~/contact</p>')
            ->toContain('me@example.com')
            ->toContain('mailto:me@example.com')
            ->toContain('run <span class="t-accent">contact</span> to send a message')
            ->not->toContain('hidden@example.com');
    });

    it('still lists an item that has no url', function () {
        ContactItem::factory()->create(['label' => 'Athens, Greece', 'url' => null]);

        expect(terminalCoreHtml('ls', 'contact'))->toContain('Athens, Greece');
    });

    it('says so when there are none', function () {
        expect(terminalCoreHtml('ls', 'contact'))->toContain('no contact info found.');
    });

    it('escapes item text', function () {
        ContactItem::factory()->create(['label' => '<b>Me</b>', 'url' => 'https://x.test/?a=1&b=2']);

        $html = terminalCoreHtml('ls', 'contact');

        expect($html)->toContain('&lt;b&gt;Me&lt;/b&gt;')->toContain('a=1&amp;b=2')->not->toContain('<b>Me');
    });
});
