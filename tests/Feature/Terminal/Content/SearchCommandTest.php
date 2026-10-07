<?php

use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillCategory;
use App\Models\TerminalCommand;
use App\Terminal\Commands\SearchCommand;

function searchCommandRun(?string $arg = null): string
{
    return app(SearchCommand::class)->handle($arg)->html;
}

describe('search command', function () {
    describe('input validation', function () {
        it('shows the usage without a query', function (?string $arg) {
            $response = app(SearchCommand::class)->handle($arg);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('Usage: search &lt;query&gt;');
        })->with([null, '', '   ']);

        it('rejects a query shorter than three characters', function (string $arg) {
            Experience::factory()->create(['title' => 'Developer']);

            expect(searchCommandRun($arg))
                ->toContain('Query too short — minimum 3 characters.')
                ->not->toContain('Developer');
        })->with(['a', 'de', '  de  ']);

        it('accepts a query of exactly three characters', function () {
            Experience::factory()->create(['title' => 'Developer']);

            expect(searchCommandRun('dev'))->toContain('1 experience match');
        });

        it('counts characters rather than bytes for the minimum length', function () {
            expect(searchCommandRun('αβ'))->toContain('Query too short');
        });
    });

    describe('no results', function () {
        it('says nothing matched', function () {
            Experience::factory()->create(['title' => 'Developer']);

            expect(searchCommandRun('zebra'))->toContain('No results for "zebra".');
        });

        it('says nothing matched when there is no content at all', function () {
            expect(searchCommandRun('anything'))->toContain('No results for "anything".');
        });

        it('escapes the query it echoes back', function () {
            expect(searchCommandRun('<script>alert(1)</script>'))
                ->toContain('No results for "&lt;script&gt;alert(1)&lt;/script&gt;".')
                ->not->toContain('<script>');
        });
    });

    describe('matching', function () {
        it('finds experience by title, company or bullet', function (string $query) {
            Experience::factory()->create([
                'title' => 'Staff Engineer',
                'company' => 'Globex Corporation',
                'bullets' => ['Migrated everything to Kubernetes'],
            ]);
            Experience::factory()->create(['title' => 'Unrelated', 'company' => 'Elsewhere', 'bullets' => ['Nothing']]);

            expect(searchCommandRun($query))
                ->toContain('1 experience match', 'Staff')
                ->not->toContain('Unrelated');
        })->with(['engineer', 'globex', 'kubernetes']);

        it('finds projects by name, subtitle, tech or bullet', function (string $query) {
            Project::factory()->create([
                'name' => 'Rocketship',
                'subtitle' => 'Launch tracker',
                'tech' => ['Rust', 'WebAssembly'],
                'bullets' => ['Handles telemetry streams'],
            ]);
            Project::factory()->create(['name' => 'Other', 'subtitle' => 'Nope', 'tech' => ['Go'], 'bullets' => ['Nothing']]);

            expect(searchCommandRun($query))
                ->toContain('1 project match', 'ship')
                ->not->toContain('Other');
        })->with(['rocket', 'tracker', 'webassembly', 'telemetry']);

        it('finds skills by category name or item and lists only the matching items', function (string $query) {
            SkillCategory::factory()->create(['name' => 'Databases', 'items' => ['PostgreSQL', 'Redis', 'SQLite']]);
            SkillCategory::factory()->create(['name' => 'Languages', 'items' => ['PHP', 'Go']]);

            $html = searchCommandRun($query);

            expect($html)->toContain('1 skill match', 'Databases')->not->toContain('Languages');
        })->with(['databases', 'redis']);

        it('only shows the matching items of a skill category', function () {
            SkillCategory::factory()->create(['name' => 'Databases', 'items' => ['PostgreSQL', 'Redis', 'SQLite']]);

            expect(searchCommandRun('redis'))
                ->toContain('<span class="t-skill-tag"><span class="t-accent">Redis</span></span>')
                ->not->toContain('PostgreSQL', 'SQLite');
        });

        it('finds education by title, institution or description', function (string $query) {
            Education::factory()->create([
                'title' => 'MSc Robotics',
                'institution' => 'Polytechnic Institute',
                'description' => 'Thesis on swarm behaviour',
            ]);
            Education::factory()->create(['title' => 'Other Degree', 'institution' => 'Elsewhere', 'description' => null]);

            expect(searchCommandRun($query))
                ->toContain('1 education match', 'Robotics')
                ->not->toContain('Other Degree');
        })->with(['robotics', 'polytechnic', 'swarm']);

        it('searches every section at once and labels each', function () {
            Experience::factory()->create(['title' => 'Laravel Developer']);
            Project::factory()->create(['name' => 'Laravel Toolkit']);
            SkillCategory::factory()->create(['name' => 'Frameworks', 'items' => ['Laravel']]);
            Education::factory()->create(['title' => 'Laravel Certified']);

            expect(searchCommandRun('laravel'))
                ->toContain('// search: laravel', '1 experience match', '1 project match', '1 skill match', '1 education match');
        });

        it('pluralises the match count', function () {
            Experience::factory()->count(2)->create(['title' => 'Laravel Developer']);

            expect(searchCommandRun('laravel'))->toContain('2 experience matches');
        });

        it('ignores letter case', function () {
            Experience::factory()->create(['title' => 'Laravel Developer']);

            expect(searchCommandRun('LARAVEL'))->toContain('1 experience match');
        });

        it('trims the query', function () {
            Experience::factory()->create(['title' => 'Laravel Developer']);

            expect(searchCommandRun('  laravel  '))->toContain('// search: laravel', '1 experience match');
        });

        it('matches a phrase containing spaces', function () {
            Experience::factory()->create(['title' => 'Senior Developer']);
            Experience::factory()->create(['title' => 'Junior Developer']);

            expect(searchCommandRun('senior dev'))->toContain('1 experience match')->not->toContain('Junior');
        });

        it('skips inactive records', function () {
            Experience::factory()->inactive()->create(['title' => 'Laravel Hidden']);
            Project::factory()->inactive()->create(['name' => 'Laravel Hidden']);
            SkillCategory::factory()->inactive()->create(['name' => 'Laravel Hidden']);
            Education::factory()->inactive()->create(['title' => 'Laravel Hidden']);

            expect(searchCommandRun('laravel'))->toContain('No results for "laravel".')->not->toContain('Hidden');
        });

        it('lists matches in sort order', function () {
            Experience::factory()->create(['title' => 'Laravel Second', 'sort_order' => 2]);
            Experience::factory()->create(['title' => 'Laravel First', 'sort_order' => 1]);

            $html = searchCommandRun('laravel');

            expect(strpos($html, 'First'))->toBeLessThan(strpos($html, 'Second'));
        });
    });

    describe('highlighting and escaping', function () {
        it('wraps the matched text in an accent span and keeps the original case', function () {
            Experience::factory()->create(['title' => 'Laravel Developer', 'company' => 'Acme']);

            expect(searchCommandRun('laravel'))
                ->toContain('<span class="t-accent"><span class="t-accent">Laravel</span> Developer</span>');
        });

        it('highlights every occurrence in a field', function () {
            Project::factory()->create(['name' => 'Go Go Gadget', 'subtitle' => 'Gadget tools']);

            expect(searchCommandRun('gadget'))
                ->toContain('Go Go <span class="t-accent">Gadget</span>', '<span class="t-accent">Gadget</span> tools');
        });

        it('escapes content while highlighting', function () {
            Experience::factory()->create(['title' => '<script>alert(1)</script>', 'company' => 'Tom & Jerry']);

            $html = searchCommandRun('script');

            expect($html)->toContain('&lt;<span class="t-accent">script</span>&gt;alert(1)&lt;/<span class="t-accent">script</span>&gt;')
                ->not->toContain('<script>');
        });

        it('never lets the query through as markup', function () {
            Experience::factory()->create(['title' => 'About <b>bold</b>']);

            expect(searchCommandRun('<b>'))->not->toContain('<b>');
        });

        it('escapes the query in the header exactly once', function () {
            Experience::factory()->create(['title' => 'Tom & Jerry']);

            expect(searchCommandRun('tom & j'))->toContain('// search: tom &amp; j')->not->toContain('&amp;amp;');
        });

        it('escapes content in every section', function () {
            Project::factory()->create(['name' => 'Evil <i>xss</i>', 'subtitle' => '<u>sub</u>']);
            SkillCategory::factory()->create(['name' => 'Evil <i>xss</i>', 'items' => ['<s>evil</s>']]);
            Education::factory()->create(['title' => 'Evil <i>xss</i>', 'institution' => '<u>evil</u>']);

            expect(searchCommandRun('evil'))
                ->toContain('&lt;i&gt;xss&lt;/i&gt;', '&lt;u&gt;sub&lt;/u&gt;', '&lt;s&gt;')
                ->not->toContain('<i>xss', '<u>', '<s>');
        });

        it('does not corrupt HTML entities when the query matches entity text', function () {
            Experience::factory()->create(['title' => 'Campus & Co']);

            expect(searchCommandRun('amp'))->toContain('&amp;')->not->toContain('&<span');
        });
    });

    describe('help', function () {
        it('prints its help with --help', function () {
            TerminalCommand::factory()->create([
                'name' => 'search',
                'command_class' => SearchCommand::class,
                'description' => 'Search across experience, projects and skills',
            ]);

            expect(searchCommandRun('--help'))->toContain('// search --help', 'Search across experience, projects and skills');
        });
    });
});
