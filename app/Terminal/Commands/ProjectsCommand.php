<?php

namespace App\Terminal\Commands;

use App\Enums\InteractionType;
use App\Models\Project;
use App\Models\TerminalCommand;
use App\Terminal\Contracts\HasStructuredData;
use App\Terminal\TerminalResponse;

class ProjectsCommand extends BaseCommand implements HasStructuredData
{
    public function name(): string
    {
        return 'projects';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        return match (true) {
            in_array($arg, ['-a', '--all'], true) => TerminalResponse::echo($this->renderAll()),
            $arg !== null && ctype_digit($arg) => TerminalResponse::echo($this->renderByNumber((int) $arg)),
            $arg !== null => TerminalResponse::echo($this->renderUnknownOption($arg)),
            default => $this->resolveInteraction(),
        };
    }

    public function helpGroup(): ?string
    {
        return 'projects';
    }

    public function helpOptions(): array
    {
        return [
            ['option' => 'projects <n>', 'description' => 'Jump directly to project n'],
            ['option' => 'projects -a', 'description' => 'All projects at once'],
        ];
    }

    public function structuredData(): array
    {
        return Project::activeOrdered()
            ->get()
            ->values()
            ->map(fn (Project $project, int $i) => [
                'n' => $i + 1,
                'name' => $project->name,
                'subtitle' => $project->subtitle,
                'html' => $this->renderItem($project),
            ])
            ->all();
    }

    private function resolveInteraction(): TerminalResponse
    {
        if (! Project::active()->exists()) {
            return TerminalResponse::echo($this->renderError('no projects entries found.'));
        }

        $record = TerminalCommand::query()->where('name', $this->name())->first();

        return match ($record?->interaction_type) {
            InteractionType::Paginate => TerminalResponse::paginate($this->name()),
            InteractionType::Selector => TerminalResponse::selector($this->name()),
            default => TerminalResponse::echo($this->renderAll()),
        };
    }

    private function renderAll(): string
    {
        $projects = Project::activeOrdered()->get();

        if ($projects->isEmpty()) {
            return $this->renderError('no projects entries found.');
        }

        $items = $projects->map(fn (Project $p) => $this->renderItem($p))->implode('');

        return <<<HTML
        <div class="t-block">
            {$this->header('projects')}
            {$items}
        </div>
        HTML;
    }

    private function renderByNumber(int $n): string
    {
        $projects = Project::activeOrdered()->get();

        if ($n < 1 || $n > $projects->count()) {
            $max = $projects->count();

            return $this->renderError("Project $n not found. Valid range: 1–$max.");
        }

        $html = $this->renderItem($projects[$n - 1]);

        return <<<HTML
        <div class="t-block">
            {$this->header('project ['.$n.']')}
            {$html}
        </div>
        HTML;
    }

    public function renderItem(Project $project): string
    {
        $name = e($project->name);
        $subtitle = e($project->subtitle ?? '');
        $tech = implode('</span> <span class="t-tag">', array_map('e', $project->tech ?? []));
        $bullets = collect($project->bullets ?? [])
            ->map(fn ($b) => '<li>'.e($b).'</li>')
            ->implode('');
        $links = collect($project->links ?? [])
            ->map(fn ($l) => '<a class="t-link" href="'.e($l['url']).'" target="_blank">'.e($l['label']).'</a>')
            ->implode('<span class="t-dim"> | </span>');

        $assets = $this->buildAssets($project);
        $mediaHtml = $this->renderMedia($name, $assets);

        return <<<HTML
         <div class="t-project-body">
            {$mediaHtml}
            <div class="t-project-info">
                <p class="t-accent t-project-title">$name</p>
                <p class="t-dim">$subtitle</p>
                <ul class="t-bullets t-mt">$bullets</ul>
                <div class="t-mt">
                    <span class="t-tag">$tech</span>
                </div>
                <div class="t-mt t-links">$links</div>
            </div>
        </div>
        HTML;
    }

    /**
     * @return array<int, array{type: string, src?: string, full?: string, embed?: string}>
     */
    private function buildAssets(Project $project): array
    {
        $assets = [];

        if ($project->imageUrl() !== '') {
            $url = e($project->imageUrl());
            $assets[] = ['type' => 'image', 'src' => $url, 'full' => $url];
        }

        foreach ($project->galleryUrls() as $url) {
            $escaped = e($url);
            $assets[] = ['type' => 'image', 'src' => $escaped, 'full' => $escaped];
        }

        $videoFileUrl = $project->videoFileUrl();
        if ($videoFileUrl !== null) {
            $assets[] = ['type' => 'video', 'src' => e($videoFileUrl)];
        }

        if (! empty($project->video_url)) {
            $embed = $this->resolveEmbedUrl($project->video_url);
            if ($embed !== '') {
                $assets[] = ['type' => 'youtube', 'embed' => e($embed)];
            }
        }

        return $assets;
    }

    /**
     * @param  array<int, array{type: string, src?: string, full?: string, embed?: string}>  $assets
     */
    private function renderMedia(string $escapedName, array $assets): string
    {
        if (empty($assets)) {
            return '';
        }

        $first = $assets[0];
        $mainImgStyle = $first['type'] === 'image' ? '' : ' style="display:none"';
        $mainVidStyle = $first['type'] !== 'image' ? '' : ' style="display:none"';
        $mainSrc = $first['src'] ?? '';
        $mainFull = $first['full'] ?? $mainSrc;
        $firstVideoInner = match ($first['type']) {
            'video' => '<video src="'.$first['src'].'" controls width="100%" height="160" style="border-radius:6px;display:block"></video>',
            'youtube' => '<iframe src="'.$first['embed'].'" width="100%" height="160" frameborder="0" allowfullscreen style="display:block"></iframe>',
            default => '',
        };

        $thumbsHtml = '';
        if (count($assets) > 1) {
            foreach ($assets as $i => $asset) {
                $active = $i === 0 ? ' t-thumb-active' : '';
                if ($asset['type'] === 'image') {
                    $thumbsHtml .= '<img class="t-project-thumb'.$active.'" data-type="image" data-src="'.$asset['src'].'" data-full="'.$asset['full'].'" src="'.$asset['src'].'" loading="lazy">';
                } elseif ($asset['type'] === 'video') {
                    $thumbsHtml .= '<div class="t-project-thumb t-thumb-video'.$active.'" data-type="video" data-src="'.$asset['src'].'">&#9654;</div>';
                } else {
                    $thumbsHtml .= '<div class="t-project-thumb t-thumb-video'.$active.'" data-type="youtube" data-embed="'.$asset['embed'].'">&#9654;</div>';
                }
            }
            $thumbsHtml = '<div class="t-project-thumbstrip">'.$thumbsHtml.'</div>';
        }

        return <<<HTML
        <div class="t-project-media">
            <div class="t-project-main-asset">
                <img class="t-project-img" src="{$mainSrc}" data-full="{$mainFull}" alt="{$escapedName}" loading="lazy"{$mainImgStyle}>
                <div class="t-project-video-embed"{$mainVidStyle}>{$firstVideoInner}</div>
            </div>
            {$thumbsHtml}
        </div>
        HTML;
    }

    private function resolveEmbedUrl(string $url): string
    {
        if (preg_match('/(?:youtube\.com\/watch\?.*v=|youtu\.be\/)([A-Za-z0-9_\-]{11})/', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return '';
    }
}
