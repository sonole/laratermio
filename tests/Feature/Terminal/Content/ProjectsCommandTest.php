<?php

use App\Enums\InteractionType;
use App\Models\Project;
use App\Models\TerminalCommand;
use App\Terminal\Commands\ProjectsCommand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/** Register the `projects` command row the way the system seeder does, with the given interaction type. */
function projectsCommandRow(?InteractionType $type = null): TerminalCommand
{
    return TerminalCommand::factory()->create([
        'name' => 'projects',
        'command_class' => ProjectsCommand::class,
        'description' => 'Side projects and open source',
        'interaction_type' => $type,
    ]);
}

function projectsCommandRun(?string $arg = null): string
{
    return app(ProjectsCommand::class)->handle($arg)->html;
}

describe('projects command', function () {
    describe('empty state', function () {
        it('reports that there are no entries', function () {
            $response = app(ProjectsCommand::class)->handle(null);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('no projects entries found.');
        });

        it('ignores inactive projects', function (?string $arg) {
            Project::factory()->inactive()->create(['name' => 'Hidden Project']);

            expect(projectsCommandRun($arg))
                ->toContain('no projects entries found.')
                ->not->toContain('Hidden Project');
        })->with([null, '-a', '--all']);

        it('does not offer the selector when nothing is active', function () {
            projectsCommandRow(InteractionType::Selector);
            Project::factory()->inactive()->create();

            $response = app(ProjectsCommand::class)->handle(null);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('no projects entries found.');
        });

        it('returns no structured data', function () {
            Project::factory()->inactive()->create();

            expect(app(ProjectsCommand::class)->structuredData())->toBe([]);
        });
    });

    describe('listing', function () {
        beforeEach(function () {
            Project::factory()->create(['name' => 'Second Project', 'sort_order' => 2]);
            Project::factory()->create(['name' => 'First Project', 'sort_order' => 1]);
            Project::factory()->inactive()->create(['name' => 'Hidden Project', 'sort_order' => 0]);
        });

        it('lists every active project in sort order', function () {
            $html = projectsCommandRun('-a');

            expect($html)->toContain('// projects', 'First Project', 'Second Project')
                ->not->toContain('Hidden Project')
                ->and(strpos($html, 'First Project'))->toBeLessThan(strpos($html, 'Second Project'));
        });

        it('treats --all like -a', function () {
            expect(projectsCommandRun('--all'))->toBe(projectsCommandRun('-a'));
        });

        it('prints everything at once when the command has no interaction type', function () {
            projectsCommandRow(null);

            $response = app(ProjectsCommand::class)->handle(null);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('First Project', 'Second Project');
        });

        it('hands over to the selector for the selector interaction', function () {
            projectsCommandRow(InteractionType::Selector);

            $response = app(ProjectsCommand::class)->handle(null);

            expect($response->toArray())->toBe(['type' => 'selector', 'key' => 'projects']);
        });

        it('hands over to the paginator for the paginate interaction', function () {
            projectsCommandRow(InteractionType::Paginate);

            $response = app(ProjectsCommand::class)->handle(null);

            expect($response->toArray())->toBe(['type' => 'paginate', 'key' => 'projects']);
        });

        it('still prints everything for -a when the command uses the selector', function () {
            projectsCommandRow(InteractionType::Selector);

            $response = app(ProjectsCommand::class)->handle('-a');

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('First Project', 'Second Project');
        });
    });

    describe('single project lookup', function () {
        beforeEach(function () {
            Project::factory()->create(['name' => 'Second Project', 'sort_order' => 2]);
            Project::factory()->create(['name' => 'First Project', 'sort_order' => 1]);
            Project::factory()->inactive()->create(['name' => 'Hidden Project', 'sort_order' => 0]);
        });

        it('jumps to the nth active project', function () {
            $html = projectsCommandRun('2');

            expect($html)->toContain('// project [2]', 'Second Project')
                ->not->toContain('First Project');
        });

        it('numbers projects from 1 and skips inactive ones', function () {
            expect(projectsCommandRun('1'))->toContain('First Project')->not->toContain('Hidden Project');
        });

        it('rejects a number outside the range', function (string $n) {
            expect(projectsCommandRun($n))->toContain("Project $n not found. Valid range: 1–2.");
        })->with(['0', '3', '99']);

        it('rejects a non-numeric argument as an unknown option', function (string $arg) {
            expect(projectsCommandRun($arg))->toContain('unknown option', $arg);
        })->with(['foo', '-1', '1.5', '-x']);

        it('escapes the argument in the unknown option message', function () {
            expect(projectsCommandRun('<script>alert(1)</script>'))
                ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
                ->not->toContain('<script>');
        });
    });

    describe('rendering', function () {
        it('shows the subtitle, bullets, tech stack and links', function () {
            Project::factory()->create([
                'name' => 'Laratermio',
                'subtitle' => 'Terminal portfolio',
                'bullets' => ['Built with Livewire', 'Admin in Filament'],
                'tech' => ['PHP', 'Laravel'],
                'links' => [['label' => 'Source', 'url' => 'https://example.com/repo'], ['label' => 'Demo', 'url' => 'https://example.com/demo']],
            ]);

            expect(projectsCommandRun('1'))
                ->toContain('Laratermio', 'Terminal portfolio', '<li>Built with Livewire</li>', '<li>Admin in Filament</li>')
                ->toContain('<span class="t-tag">PHP</span> <span class="t-tag">Laravel</span>')
                ->toContain('<a class="t-link" href="https://example.com/repo" target="_blank">Source</a>', '<a class="t-link" href="https://example.com/demo" target="_blank">Demo</a>');
        });

        it('renders a project with only a name', function () {
            Project::factory()->create(['name' => 'Bare Project', 'subtitle' => null, 'tech' => null, 'bullets' => null, 'links' => null]);

            expect(projectsCommandRun('1'))
                ->toContain('Bare Project', '<ul class="t-bullets t-mt"></ul>')
                ->not->toContain('<a class="t-link"');
        });

        it('escapes every content field', function () {
            Project::factory()->create([
                'name' => '<script>alert(1)</script>',
                'subtitle' => 'Tom & <b>Jerry</b>',
                'bullets' => ['<img src=x onerror=alert(1)>'],
                'tech' => ['<i>PHP</i>'],
                'links' => [['label' => '<u>Repo</u>', 'url' => 'https://example.com/?a=1&b=2"><script>x</script>']],
            ]);

            expect(projectsCommandRun('-a'))
                ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;', 'Tom &amp; &lt;b&gt;Jerry&lt;/b&gt;', '&lt;img src=x onerror=alert(1)&gt;', '&lt;i&gt;PHP&lt;/i&gt;', '&lt;u&gt;Repo&lt;/u&gt;', 'a=1&amp;b=2&quot;&gt;&lt;script&gt;')
                ->not->toContain('<script>', '<b>Jerry', '<img', '<i>PHP', '<u>Repo');
        });

        describe('media', function () {
            beforeEach(function () {
                $this->projectsMediaRoot = storage_path('framework/testing/disks/terminal-content');
                config(['filesystems.disks.public.root' => $this->projectsMediaRoot]);
                Storage::forgetDisk('public');
            });

            afterEach(function () {
                File::deleteDirectory($this->projectsMediaRoot);
            });

            it('renders no media block for a project without media', function () {
                Project::factory()->create(['video_url' => null]);

                expect(projectsCommandRun('1'))->not->toContain('t-project-media');
            });

            it('embeds a YouTube video through the privacy-friendly domain', function (string $url) {
                Project::factory()->create(['video_url' => $url]);

                expect(projectsCommandRun('1'))
                    ->toContain('t-project-media', '<iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"');
            })->with([
                'watch url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'short url' => 'https://youtu.be/dQw4w9WgXcQ',
            ]);

            it('embeds a Vimeo video', function () {
                Project::factory()->create(['video_url' => 'https://vimeo.com/123456789']);

                expect(projectsCommandRun('1'))->toContain('<iframe src="https://player.vimeo.com/video/123456789"');
            });

            it('ignores a video URL from an unsupported host', function () {
                Project::factory()->create(['video_url' => 'https://example.com/video/123']);

                expect(projectsCommandRun('1'))->not->toContain('t-project-media')->not->toContain('<iframe');
            });

            it('shows the main image and a thumbnail strip for several assets', function () {
                $project = Project::factory()->create(['name' => 'Gallery Project', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
                $main = UploadedFile::fake()->image('main.jpg');
                $shot = UploadedFile::fake()->image('shot.png');
                $project->addMedia($main)->preservingOriginal()->toMediaCollection('main_image');
                $project->addMedia($shot)->preservingOriginal()->toMediaCollection('gallery');

                $html = projectsCommandRun('1');

                expect($html)->toContain('t-project-media', 'class="t-project-img"', 'alt="Gallery Project"', 'main.jpg', 'shot.png')
                    ->toContain('t-project-thumbstrip', 'data-type="image"', 'data-type="youtube"');
            });

            it('skips the thumbnail strip when there is a single asset', function () {
                $project = Project::factory()->create(['video_url' => null]);
                $main = UploadedFile::fake()->image('main.jpg');
                $project->addMedia($main)->preservingOriginal()->toMediaCollection('main_image');

                expect(projectsCommandRun('1'))->toContain('t-project-media', 'main.jpg')->not->toContain('t-project-thumbstrip');
            });
        });
    });

    describe('structured data', function () {
        it('lists active projects in order with numbered payloads', function () {
            Project::factory()->create(['name' => 'Second Project', 'subtitle' => 'Second subtitle', 'sort_order' => 2]);
            Project::factory()->create(['name' => 'First Project', 'subtitle' => 'First subtitle', 'sort_order' => 1]);
            Project::factory()->inactive()->create(['sort_order' => 0]);

            $data = app(ProjectsCommand::class)->structuredData();

            expect($data)->toHaveCount(2)
                ->and(array_keys($data[0]))->toBe(['n', 'name', 'subtitle', 'html'])
                ->and($data[0]['n'])->toBe(1)
                ->and($data[0]['name'])->toBe('First Project')
                ->and($data[0]['subtitle'])->toBe('First subtitle')
                ->and($data[0]['html'])->toContain('First Project', 'First subtitle')
                ->and($data[1]['n'])->toBe(2)
                ->and($data[1]['name'])->toBe('Second Project');
        });

        it('escapes the name and subtitle, which the selector injects as raw HTML', function () {
            Project::factory()->create(['name' => '<img src=x onerror=alert(1)>', 'subtitle' => 'A & B']);

            $data = app(ProjectsCommand::class)->structuredData();

            expect($data[0]['name'])->not->toContain('<img')
                ->and($data[0]['subtitle'])->toContain('A &amp; B');
        });

        it('returns an empty string as the subtitle of a project without one', function () {
            Project::factory()->create(['subtitle' => null]);

            expect(app(ProjectsCommand::class)->structuredData()[0]['subtitle'])->toBe('');
        });
    });

    describe('help', function () {
        it('documents the options', function (string $flag) {
            projectsCommandRow();

            expect(projectsCommandRun($flag))
                ->toContain('// projects --help', 'Side projects and open source', 'projects &lt;n&gt;', 'projects -a');
        })->with(['-h', '--help']);
    });
});
