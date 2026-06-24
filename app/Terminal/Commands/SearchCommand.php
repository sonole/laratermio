<?php

namespace App\Terminal\Commands;

use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillCategory;
use App\Terminal\TerminalResponse;
use Illuminate\Support\Collection;

class SearchCommand extends BaseCommand
{
    public function name(): string
    {
        return 'search';
    }

    public function helpGroup(): ?string
    {
        return 'explore';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        if ($arg === null || trim($arg) === '') {
            return TerminalResponse::echo($this->renderError('Usage: search &lt;query&gt;'));
        }

        $query = trim($arg);

        if (strlen($query) < 3) {
            return TerminalResponse::echo($this->renderError('Query too short — minimum 3 characters.'));
        }

        $experiences = $this->matchExperiences($query);
        $projects = $this->matchProjects($query);
        $skills = $this->matchSkills($query);
        $educations = $this->matchEducations($query);

        if ($experiences->isEmpty() && $projects->isEmpty() && $skills->isEmpty() && $educations->isEmpty()) {
            $safe = e($query);

            return TerminalResponse::echo($this->renderError("No results for \"$safe\"."));
        }

        $sections = $this->renderExperienceResults($experiences, $query);
        $sections .= $this->renderProjectResults($projects, $query);
        $sections .= $this->renderSkillResults($skills, $query);
        $sections .= $this->renderEducationResults($educations, $query);

        return TerminalResponse::echo(<<<HTML
        <div class="t-block">
            {$this->header('search: '.e($query))}
            $sections
        </div>
        HTML);
    }

    /** @return Collection<int, Experience> */
    private function matchExperiences(string $query): Collection
    {
        return Experience::activeOrdered()
            ->get()
            ->filter(fn (Experience $exp) => $this->matches($exp->title, $query) ||
                $this->matches($exp->company, $query) ||
                collect($exp->bullets ?? [])->contains(fn ($b) => $this->matches($b, $query))
            );
    }

    /** @return Collection<int, Project> */
    private function matchProjects(string $query): Collection
    {
        return Project::activeOrdered()
            ->get()
            ->filter(fn (Project $project) => $this->matches($project->name, $query) ||
                $this->matches($project->subtitle ?? '', $query) ||
                collect($project->tech ?? [])->contains(fn ($t) => $this->matches($t, $query)) ||
                collect($project->bullets ?? [])->contains(fn ($b) => $this->matches($b, $query))
            );
    }

    /** @return Collection<int, SkillCategory> */
    private function matchSkills(string $query): Collection
    {
        return SkillCategory::activeOrdered()
            ->get()
            ->filter(fn (SkillCategory $cat) => $this->matches($cat->name, $query) ||
                collect($cat->items ?? [])->contains(fn ($i) => $this->matches($i, $query))
            );
    }

    /** @param Collection<int, Experience> $items */
    private function renderExperienceResults(Collection $items, string $query): string
    {
        if ($items->isEmpty()) {
            return '';
        }

        $rows = $items->map(function (Experience $exp) use ($query) {
            $title = $this->highlight($exp->title, $query);
            $company = $this->highlight($exp->company, $query);
            $period = e($exp->period ?? '');

            return <<<HTML
            <div class="t-search-result">
                <span class="t-accent">$title</span>
                <span class="t-dim"> @ </span>
                <span class="t-label">$company</span>
                <span class="t-period">$period</span>
            </div>
            HTML;
        })->implode('');

        $count = $items->count();
        $label = $count === 1 ? '1 experience match' : "$count experience matches";

        return <<<HTML
        <div class="t-search-section">
            <p class="t-search-group">$label</p>
            $rows
        </div>
        HTML;
    }

    /** @param Collection<int, Project> $items */
    private function renderProjectResults(Collection $items, string $query): string
    {
        if ($items->isEmpty()) {
            return '';
        }

        $rows = $items->map(function (Project $project) use ($query) {
            $name = $this->highlight($project->name, $query);
            $subtitle = $this->highlight($project->subtitle ?? '', $query);

            return <<<HTML
            <div class="t-search-result">
                <span class="t-accent">$name</span>
                <span class="t-dim"> — </span>
                <span>$subtitle</span>
            </div>
            HTML;
        })->implode('');

        $count = $items->count();
        $label = $count === 1 ? '1 project match' : "$count project matches";

        return <<<HTML
        <div class="t-search-section">
            <p class="t-search-group">$label</p>
            $rows
        </div>
        HTML;
    }

    /** @param Collection<int, SkillCategory> $items */
    private function renderSkillResults(Collection $items, string $query): string
    {
        if ($items->isEmpty()) {
            return '';
        }

        $rows = $items->map(function (SkillCategory $cat) use ($query) {
            $catName = $this->highlight($cat->name, $query);
            $matchingItems = collect($cat->items ?? [])
                ->filter(fn ($i) => $this->matches($i, $query))
                ->map(fn ($i) => '<span class="t-skill-tag">'.$this->highlight($i, $query).'</span>')
                ->implode(' ');

            return <<<HTML
            <div class="t-search-result">
                <span class="t-label">$catName</span>
                <span class="t-dim"> → </span>
                $matchingItems
            </div>
            HTML;
        })->implode('');

        $count = $items->count();
        $label = $count === 1 ? '1 skill match' : "$count skill matches";

        return <<<HTML
        <div class="t-search-section">
            <p class="t-search-group">$label</p>
            $rows
        </div>
        HTML;
    }

    /** @return Collection<int, Education> */
    private function matchEducations(string $query): Collection
    {
        return Education::activeOrdered()
            ->get()
            ->filter(fn (Education $edu) => $this->matches($edu->title, $query) ||
                $this->matches($edu->institution, $query) ||
                ($edu->description && $this->matches($edu->description, $query))
            );
    }

    /** @param Collection<int, Education> $items */
    private function renderEducationResults(Collection $items, string $query): string
    {
        if ($items->isEmpty()) {
            return '';
        }

        $rows = $items->map(function (Education $edu) use ($query) {
            $title = $this->highlight($edu->title, $query);
            $institution = $this->highlight($edu->institution, $query);
            $period = e($edu->period);

            return <<<HTML
            <div class="t-search-result">
                <span class="t-accent">$title</span>
                <span class="t-dim"> @ </span>
                <span class="t-label">$institution</span>
                <span class="t-period">$period</span>
            </div>
            HTML;
        })->implode('');

        $count = $items->count();
        $label = $count === 1 ? '1 education match' : "$count education matches";

        return <<<HTML
        <div class="t-search-section">
            <p class="t-search-group">$label</p>
            $rows
        </div>
        HTML;
    }

    private function matches(string $text, string $query): bool
    {
        return stripos($text, $query) !== false;
    }

    private function highlight(string $text, string $query): string
    {
        $escaped = e($text);
        $pattern = '/('.preg_quote(e($query), '/').')/i';

        return preg_replace($pattern, '<span class="t-accent">$1</span>', $escaped) ?? $escaped;
    }
}
