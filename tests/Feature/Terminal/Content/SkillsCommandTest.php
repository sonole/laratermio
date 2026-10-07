<?php

use App\Models\SkillCategory;
use App\Models\TerminalCommand;
use App\Terminal\Commands\SkillsCommand;

function skillsCommandRun(?string $arg = null): string
{
    return app(SkillsCommand::class)->handle($arg)->html;
}

describe('skills command', function () {
    describe('empty state', function () {
        it('reports that there are no entries', function () {
            $response = app(SkillsCommand::class)->handle(null);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('no skills entries found.');
        });

        it('ignores inactive categories', function () {
            SkillCategory::factory()->inactive()->create(['name' => 'Hidden Category']);

            expect(skillsCommandRun())
                ->toContain('no skills entries found.')
                ->not->toContain('Hidden Category');
        });
    });

    describe('listing', function () {
        it('lists active categories in sort order with their items', function () {
            SkillCategory::factory()->create(['name' => 'Frontend', 'items' => ['Vue', 'Tailwind'], 'sort_order' => 2]);
            SkillCategory::factory()->create(['name' => 'Backend', 'items' => ['PHP', 'Laravel'], 'sort_order' => 1]);
            SkillCategory::factory()->inactive()->create(['name' => 'Hidden Category', 'items' => ['Secret'], 'sort_order' => 0]);

            $html = skillsCommandRun();

            expect($html)->toContain('// skills', 'Backend', 'Frontend')
                ->toContain('<span class="t-skill-tag">PHP</span>', '<span class="t-skill-tag">Laravel</span>', '<span class="t-skill-tag">Vue</span>', '<span class="t-skill-tag">Tailwind</span>')
                ->not->toContain('Hidden Category', 'Secret')
                ->and(strpos($html, 'Backend'))->toBeLessThan(strpos($html, 'Frontend'));
        });

        it('keeps the item order inside a category', function () {
            SkillCategory::factory()->create(['items' => ['Zeta', 'Alpha']]);

            $html = skillsCommandRun();

            expect(strpos($html, 'Zeta'))->toBeLessThan(strpos($html, 'Alpha'));
        });

        it('renders a category without items', function () {
            SkillCategory::factory()->create(['name' => 'Empty Category', 'items' => []]);

            expect(skillsCommandRun())
                ->toContain('Empty Category', '<div class="t-skill-tags"></div>')
                ->not->toContain('t-skill-tag"');
        });

        it('escapes the skill items', function () {
            SkillCategory::factory()->create(['items' => ['C++ & <b>C#</b>', '<script>alert(1)</script>']]);

            expect(skillsCommandRun())
                ->toContain('C++ &amp; &lt;b&gt;C#&lt;/b&gt;', '&lt;script&gt;alert(1)&lt;/script&gt;')
                ->not->toContain('<script>', '<b>C#');
        });

        it('escapes the category name', function () {
            SkillCategory::factory()->create(['name' => '<script>alert(1)</script>']);

            expect(skillsCommandRun())->not->toContain('<script>');
        });
    });

    describe('options', function () {
        it('rejects any argument as an unknown option', function (string $arg) {
            SkillCategory::factory()->create();

            expect(skillsCommandRun($arg))->toContain('unknown option', $arg)->not->toContain('// skills');
        })->with(['-a', '1', 'foo']);

        it('escapes the argument in the unknown option message', function () {
            expect(skillsCommandRun('<script>alert(1)</script>'))
                ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
                ->not->toContain('<script>');
        });

        it('prints its help with -h', function () {
            TerminalCommand::factory()->create([
                'name' => 'skills',
                'command_class' => SkillsCommand::class,
                'description' => 'Technical skills and stack',
            ]);

            expect(skillsCommandRun('-h'))->toContain('// skills --help', 'Technical skills and stack');
        });
    });
});
