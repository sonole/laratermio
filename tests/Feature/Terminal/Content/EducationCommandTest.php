<?php

use App\Models\Education;
use App\Models\TerminalCommand;
use App\Terminal\Commands\EducationCommand;

function educationCommandRun(?string $arg = null): string
{
    return app(EducationCommand::class)->handle($arg)->html;
}

describe('education command', function () {
    describe('empty state', function () {
        it('reports that there are no entries', function () {
            $response = app(EducationCommand::class)->handle(null);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('no education entries found.');
        });

        it('ignores inactive entries', function () {
            Education::factory()->inactive()->create(['title' => 'Hidden Degree']);

            expect(educationCommandRun())
                ->toContain('no education entries found.')
                ->not->toContain('Hidden Degree');
        });
    });

    describe('listing', function () {
        it('lists active entries in sort order and skips inactive ones', function () {
            Education::factory()->create(['title' => 'Second Degree', 'institution' => 'Beta University', 'sort_order' => 2]);
            Education::factory()->create(['title' => 'First Degree', 'institution' => 'Alpha University', 'sort_order' => 1]);
            Education::factory()->inactive()->create(['title' => 'Hidden Degree', 'sort_order' => 0]);

            $html = educationCommandRun();

            expect($html)->toContain('// education', 'First Degree', 'Alpha University', 'Second Degree', 'Beta University')
                ->not->toContain('Hidden Degree')
                ->and(strpos($html, 'First Degree'))->toBeLessThan(strpos($html, 'Second Degree'));
        });

        it('separates degrees from certifications', function () {
            Education::factory()->create(['title' => 'BSc Computing', 'sort_order' => 1]);
            Education::factory()->certification()->create(['title' => 'AWS Certified', 'sort_order' => 2]);

            $html = educationCommandRun();

            expect($html)->toContain('// degrees', '// certifications')
                ->and(strpos($html, '// degrees'))->toBeLessThan(strpos($html, 'BSc Computing'))
                ->and(strpos($html, 'BSc Computing'))->toBeLessThan(strpos($html, '// certifications'))
                ->and(strpos($html, '// certifications'))->toBeLessThan(strpos($html, 'AWS Certified'));
        });

        it('lists certifications under their own heading even when they sort before degrees', function () {
            Education::factory()->certification()->create(['title' => 'AWS Certified', 'sort_order' => 1]);
            Education::factory()->create(['title' => 'BSc Computing', 'sort_order' => 2]);

            $html = educationCommandRun();

            expect(strpos($html, 'BSc Computing'))->toBeLessThan(strpos($html, 'AWS Certified'));
        });

        it('omits the certifications heading when there are only degrees', function () {
            Education::factory()->create(['title' => 'BSc Computing']);

            expect(educationCommandRun())->toContain('// degrees')->not->toContain('// certifications');
        });

        it('omits the degrees heading when there are only certifications', function () {
            Education::factory()->certification()->create(['title' => 'AWS Certified']);

            expect(educationCommandRun())->toContain('// certifications')->not->toContain('// degrees');
        });
    });

    describe('rendering', function () {
        it('shows the period of a degree', function () {
            Education::factory()->create(['start_date' => '2012-09-01', 'end_date' => '2017-06-01']);

            expect(educationCommandRun())->toContain('Sep 2012 – Jun 2017');
        });

        it('shows "Present" for a degree that has not ended', function () {
            Education::factory()->create(['start_date' => '2024-09-01', 'end_date' => null]);

            expect(educationCommandRun())->toContain('Sep 2024 – Present');
        });

        it('shows the issue date of a certification', function () {
            Education::factory()->certification()->create(['start_date' => '2022-11-01']);

            expect(educationCommandRun())->toContain('Issued on Nov 2022');
        });

        it('renders the description with line breaks', function () {
            Education::factory()->create(['description' => "First line\nSecond line"]);

            expect(educationCommandRun())->toContain('<p class="t-paragraph">First line<br />', 'Second line</p>');
        });

        it('renders no description paragraph without a description', function () {
            Education::factory()->create(['description' => null]);

            expect(educationCommandRun())->not->toContain('t-paragraph');
        });

        it('links to the certificate when a URL is set', function () {
            Education::factory()->certification()->create(['certificate_url' => 'https://example.com/cert?id=1&lang=en']);

            expect(educationCommandRun())
                ->toContain('href="https://example.com/cert?id=1&amp;lang=en"', 'Certificate URL', 'target="_blank"');
        });

        it('renders no certificate link without a URL', function () {
            Education::factory()->certification()->create(['certificate_url' => null]);

            expect(educationCommandRun())->not->toContain('Certificate URL');
        });

        it('escapes every content field', function () {
            Education::factory()->create([
                'title' => '<script>alert(1)</script>',
                'institution' => 'Tom & <b>Jerry</b> College',
                'description' => '<img src=x onerror=alert(1)>',
                'certificate_url' => 'https://example.com/"><script>x</script>',
            ]);

            expect(educationCommandRun())
                ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;', 'Tom &amp; &lt;b&gt;Jerry&lt;/b&gt; College', '&lt;img src=x onerror=alert(1)&gt;', '&quot;&gt;&lt;script&gt;')
                ->not->toContain('<script>', '<b>Jerry', '<img');
        });
    });

    describe('options', function () {
        it('rejects any argument as an unknown option', function (string $arg) {
            Education::factory()->create();

            expect(educationCommandRun($arg))->toContain('unknown option', $arg)->not->toContain('// education');
        })->with(['-a', '1', 'foo']);

        it('escapes the argument in the unknown option message', function () {
            expect(educationCommandRun('<script>alert(1)</script>'))
                ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
                ->not->toContain('<script>');
        });

        it('prints its help with --help', function () {
            TerminalCommand::factory()->create([
                'name' => 'education',
                'command_class' => EducationCommand::class,
                'description' => 'Education & certifications',
            ]);

            expect(educationCommandRun('--help'))->toContain('// education --help', 'Education &amp; certifications');
        });
    });
});
