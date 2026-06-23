<?php

namespace App\Terminal\Commands;

use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillCategory;
use App\Terminal\TerminalContext;
use App\Terminal\TerminalResponse;

class LsCommand extends BaseCommand
{
    public function __construct(private readonly TerminalContext $context) {}

    public function name(): string
    {
        return 'ls';
    }

    public function helpGroup(): ?string
    {
        return 'system';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        $dir = $this->resolveDir($arg);

        if ($dir === null) {
            return TerminalResponse::echo($this->renderRoot());
        }

        if (! in_array($dir, TerminalContext::FILESYSTEM_ROOTS, true)) {
            return TerminalResponse::echo($this->renderError('ls: '.e($arg ?? $dir).': no such directory'));
        }

        return TerminalResponse::echo($this->renderSection($dir));
    }

    private function resolveDir(?string $arg): ?string
    {
        if ($arg !== null) {
            $cleaned = ltrim(str_replace('~/', '', $arg), '/');

            return ($cleaned !== '' && $cleaned !== '~') ? $cleaned : null;
        }

        $cwd = $this->context->getCwd();

        return $cwd !== '~' ? ltrim(str_replace('~/', '', $cwd), '/') : null;
    }

    private function renderRoot(): string
    {
        $entries = [
            ['name' => 'projects/', 'desc' => 'side projects &amp; open source'],
            ['name' => 'skills/', 'desc' => 'technical stack &amp; tools'],
            ['name' => 'experience/', 'desc' => 'work history'],
            ['name' => 'education/', 'desc' => 'degrees &amp; certifications'],
            ['name' => 'contact/', 'desc' => 'get in touch'],
        ];

        $rows = implode('', array_map(
            fn ($e) => '<div class="t-ls-row"><span class="t-accent t-ls-name">'.$e['name'].'</span><span class="t-dim">'.$e['desc'].'</span></div>',
            $entries
        ));

        return '<div class="t-block"><p class="t-header">// ls ~/</p><div class="t-ls">'.$rows.'</div><p class="t-dim t-mt">use <span class="t-accent">cd &lt;dir&gt;</span> to navigate</p></div>';
    }

    private function renderSection(string $section): string
    {
        return match ($section) {
            'projects' => $this->renderProjects(),
            'skills' => $this->renderSkills(),
            'experience' => $this->renderExperience(),
            'education' => $this->renderEducation(),
            'contact' => $this->renderContact(),
            default => $this->renderError("$section: directory not found"),
        };
    }

    private function renderProjects(): string
    {
        $rows = Project::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Project $p) => '<div class="t-ls-row"><span class="t-accent t-ls-name">'.e($p->name).'</span><span class="t-dim">'.e($p->subtitle ?? '').'</span></div>')
            ->implode('');

        if ($rows === '') {
            return $this->renderError('no projects found.');
        }

        return '<div class="t-block"><p class="t-header">// ls ~/projects</p><div class="t-ls">'.$rows.'</div><p class="t-dim t-mt">run <span class="t-accent">projects</span> to explore</p></div>';
    }

    private function renderSkills(): string
    {
        $rows = SkillCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SkillCategory $c) => '<div class="t-ls-row"><span class="t-accent t-ls-name">'.e($c->name).'</span><span class="t-dim">'.e(implode(', ', array_slice($c->items ?? [], 0, 4))).'…</span></div>')
            ->implode('');

        if ($rows === '') {
            return $this->renderError('no skill categories found.');
        }

        return '<div class="t-block"><p class="t-header">// ls ~/skills</p><div class="t-ls">'.$rows.'</div><p class="t-dim t-mt">run <span class="t-accent">skills</span> to see details</p></div>';
    }

    private function renderExperience(): string
    {
        $rows = Experience::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Experience $e) => '<div class="t-ls-row"><span class="t-accent t-ls-name">'.e($e->title).'</span><span class="t-dim">'.e($e->company).' &mdash; '.e($e->period).'</span></div>')
            ->implode('');

        if ($rows === '') {
            return $this->renderError('no experience entries found.');
        }

        return '<div class="t-block"><p class="t-header">// ls ~/experience</p><div class="t-ls">'.$rows.'</div><p class="t-dim t-mt">run <span class="t-accent">experience</span> for full details</p></div>';
    }

    private function renderEducation(): string
    {
        $rows = Education::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Education $e) => '<div class="t-ls-row"><span class="t-accent t-ls-name">'.e($e->title).'</span><span class="t-dim">'.e($e->institution).' &mdash; '.e($e->period).'</span></div>')
            ->implode('');

        if ($rows === '') {
            return $this->renderError('no education entries found.');
        }

        return '<div class="t-block"><p class="t-header">// ls ~/education</p><div class="t-ls">'.$rows.'</div><p class="t-dim t-mt">run <span class="t-accent">education</span> for full details</p></div>';
    }

    private function renderContact(): string
    {
        $rows = ContactItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ContactItem $c) => '<div class="t-ls-row"><span class="t-accent t-ls-name">'.e($c->label).'</span><span class="t-dim">'.e($c->url ?? '').'</span></div>')
            ->implode('');

        if ($rows === '') {
            return $this->renderError('no contact info found.');
        }

        return '<div class="t-block"><p class="t-header">// ls ~/contact</p><div class="t-ls">'.$rows.'</div><p class="t-dim t-mt">run <span class="t-accent">contact</span> to send a message</p></div>';
    }
}
