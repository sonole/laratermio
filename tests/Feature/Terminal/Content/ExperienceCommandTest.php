<?php

use App\Enums\InteractionType;
use App\Models\Experience;
use App\Models\TerminalCommand;
use App\Terminal\Commands\ExperienceCommand;

/** Register the `experience` command row the way the system seeder does, with the given interaction type. */
function experienceCommandRow(?InteractionType $type = null): TerminalCommand
{
    return TerminalCommand::factory()->create([
        'name' => 'experience',
        'command_class' => ExperienceCommand::class,
        'description' => 'Work history',
        'interaction_type' => $type,
    ]);
}

function experienceCommandRun(?string $arg = null): string
{
    return app(ExperienceCommand::class)->handle($arg)->html;
}

describe('experience command', function () {
    describe('empty state', function () {
        it('reports that there are no entries', function () {
            $response = app(ExperienceCommand::class)->handle(null);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('no experience entries found.');
        });

        it('ignores inactive entries', function (?string $arg) {
            Experience::factory()->inactive()->create(['title' => 'Hidden Role']);

            expect(experienceCommandRun($arg))
                ->toContain('no experience entries found.')
                ->not->toContain('Hidden Role');
        })->with([null, '-a', '--all']);

        it('does not offer pagination when nothing is active', function () {
            experienceCommandRow(InteractionType::Paginate);
            Experience::factory()->inactive()->create();

            $response = app(ExperienceCommand::class)->handle(null);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('no experience entries found.');
        });

        it('returns no structured data', function () {
            Experience::factory()->inactive()->create();

            expect(app(ExperienceCommand::class)->structuredData())->toBe([]);
        });
    });

    describe('listing', function () {
        beforeEach(function () {
            Experience::factory()->create(['title' => 'Second Role', 'company' => 'Beta Corp', 'sort_order' => 2]);
            Experience::factory()->create(['title' => 'First Role', 'company' => 'Alpha Corp', 'sort_order' => 1]);
            Experience::factory()->inactive()->create(['title' => 'Hidden Role', 'sort_order' => 0]);
        });

        it('lists every active entry in sort order', function () {
            $html = experienceCommandRun('-a');

            expect($html)->toContain('// experience', 'First Role', 'Alpha Corp', 'Second Role', 'Beta Corp')
                ->not->toContain('Hidden Role')
                ->and(strpos($html, 'First Role'))->toBeLessThan(strpos($html, 'Second Role'));
        });

        it('treats --all like -a', function () {
            expect(experienceCommandRun('--all'))->toBe(experienceCommandRun('-a'));
        });

        it('prints everything at once when the command has no interaction type', function () {
            experienceCommandRow(null);

            $response = app(ExperienceCommand::class)->handle(null);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('First Role', 'Second Role');
        });

        it('prints everything at once when the command row is missing', function () {
            $response = app(ExperienceCommand::class)->handle(null);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('First Role');
        });

        it('hands over to the paginator for the paginate interaction', function () {
            experienceCommandRow(InteractionType::Paginate);

            $response = app(ExperienceCommand::class)->handle(null);

            expect($response->toArray())->toBe(['type' => 'paginate', 'key' => 'experience']);
        });

        it('hands over to the selector for the selector interaction', function () {
            experienceCommandRow(InteractionType::Selector);

            $response = app(ExperienceCommand::class)->handle(null);

            expect($response->toArray())->toBe(['type' => 'selector', 'key' => 'experience']);
        });

        it('still prints everything for -a when the command paginates', function () {
            experienceCommandRow(InteractionType::Paginate);

            $response = app(ExperienceCommand::class)->handle('-a');

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('First Role', 'Second Role');
        });
    });

    describe('single entry lookup', function () {
        beforeEach(function () {
            Experience::factory()->create(['title' => 'Second Role', 'sort_order' => 2]);
            Experience::factory()->create(['title' => 'First Role', 'sort_order' => 1]);
            Experience::factory()->inactive()->create(['title' => 'Hidden Role', 'sort_order' => 0]);
        });

        it('jumps to the nth active entry', function () {
            $html = experienceCommandRun('2');

            expect($html)->toContain('// experience [2]', 'Second Role')
                ->not->toContain('First Role');
        });

        it('numbers entries from 1 and skips inactive ones', function () {
            expect(experienceCommandRun('1'))->toContain('First Role')->not->toContain('Hidden Role');
        });

        it('rejects a number outside the range', function (string $n) {
            expect(experienceCommandRun($n))->toContain("Experience $n not found. Valid range: 1–2.");
        })->with(['0', '3', '99']);

        it('rejects a non-numeric argument as an unknown option', function (string $arg) {
            expect(experienceCommandRun($arg))->toContain('unknown option', $arg);
        })->with(['foo', '-1', '1.5', '-x']);

        it('escapes the argument in the unknown option message', function () {
            $html = experienceCommandRun('<script>alert(1)</script>');

            expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
                ->not->toContain('<script>');
        });
    });

    describe('rendering', function () {
        it('shows the period and bullets of an entry', function () {
            Experience::factory()->create([
                'start_date' => '2020-03-01',
                'end_date' => '2021-05-01',
                'bullets' => ['Shipped the API', 'Mentored juniors'],
            ]);

            expect(experienceCommandRun('1'))
                ->toContain('Mar 2020 – May 2021', '<li>Shipped the API</li>', '<li>Mentored juniors</li>')
                ->not->toContain('t-badge');
        });

        it('marks the current job and shows "Present" as its end', function () {
            Experience::factory()->current()->create(['start_date' => '2022-01-01']);

            expect(experienceCommandRun('1'))
                ->toContain('<span class="t-badge">current</span>', 'Jan 2022 – Present');
        });

        it('renders an entry without bullets', function () {
            Experience::factory()->create(['bullets' => null]);

            expect(experienceCommandRun('1'))->toContain('<ul class="t-bullets"></ul>');
        });

        it('escapes every content field', function () {
            Experience::factory()->create([
                'title' => '<script>alert(1)</script>',
                'company' => 'Tom & <b>Jerry</b>',
                'bullets' => ['<img src=x onerror=alert(1)>'],
            ]);

            expect(experienceCommandRun('-a'))
                ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;', 'Tom &amp; &lt;b&gt;Jerry&lt;/b&gt;', '&lt;img src=x onerror=alert(1)&gt;')
                ->not->toContain('<script>', '<b>Jerry', '<img');
        });
    });

    describe('structured data', function () {
        it('lists active entries in order with numbered payloads', function () {
            Experience::factory()->create(['title' => 'Second Role', 'company' => 'Beta Corp', 'start_date' => '2021-01-01', 'end_date' => null, 'sort_order' => 2]);
            Experience::factory()->create(['title' => 'First Role', 'company' => 'Alpha Corp', 'start_date' => '2019-04-01', 'end_date' => '2020-12-01', 'sort_order' => 1]);
            Experience::factory()->inactive()->create(['sort_order' => 0]);

            $data = app(ExperienceCommand::class)->structuredData();

            expect($data)->toHaveCount(2)
                ->and(array_keys($data[0]))->toBe(['n', 'name', 'subtitle', 'html'])
                ->and($data[0]['n'])->toBe(1)
                ->and($data[0]['name'])->toBe('First Role')
                ->and($data[0]['subtitle'])->toBe('Alpha Corp · Apr 2019 – Dec 2020')
                ->and($data[0]['html'])->toContain('First Role', 'Alpha Corp')
                ->and($data[1]['n'])->toBe(2)
                ->and($data[1]['name'])->toBe('Second Role')
                ->and($data[1]['subtitle'])->toBe('Beta Corp · Jan 2021 – Present');
        });

        it('escapes the name and subtitle, which the selector injects as raw HTML', function () {
            Experience::factory()->create(['title' => '<img src=x onerror=alert(1)>', 'company' => 'A & B']);

            $data = app(ExperienceCommand::class)->structuredData();

            expect($data[0]['name'])->not->toContain('<img')
                ->and($data[0]['subtitle'])->toContain('A &amp; B');
        });
    });

    describe('help', function () {
        it('documents the options', function (string $flag) {
            experienceCommandRow();

            $html = experienceCommandRun($flag);

            expect($html)->toContain('// experience --help', 'Work history', 'experience &lt;n&gt;', 'experience -a');
        })->with(['-h', '--help']);
    });
});
