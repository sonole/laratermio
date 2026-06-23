<?php

namespace App\Terminal\Commands;

use App\Enums\InteractionType;
use App\Models\Experience;
use App\Models\TerminalCommand;
use App\Terminal\Contracts\HasStructuredData;
use App\Terminal\TerminalResponse;

class ExperienceCommand extends BaseCommand implements HasStructuredData
{
    public function name(): string
    {
        return 'experience';
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
        return 'experience';
    }

    public function helpOptions(): array
    {
        return [
            ['option' => 'experience <n>', 'description' => 'Jump directly to experience n'],
            ['option' => 'experience -a', 'description' => 'Full work history at once'],
        ];
    }

    public function structuredData(): array
    {
        return Experience::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->values()
            ->map(fn (Experience $exp, int $i) => [
                'n' => $i + 1,
                'name' => $exp->title,
                'subtitle' => $exp->company.($exp->period ? ' · '.$exp->period : ''),
                'html' => $this->renderItem($exp),
            ])
            ->all();
    }

    private function resolveInteraction(): TerminalResponse
    {
        if (! Experience::query()->where('is_active', true)->exists()) {
            return TerminalResponse::echo($this->renderError('no experience entries found.'));
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
        $items = Experience::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($items->isEmpty()) {
            return $this->renderError('no experience entries found.');
        }

        $items = $items->map(fn (Experience $exp) => $this->renderItem($exp))
            ->implode('');

        return <<<HTML
        <div class="t-block">
            {$this->header('experience')}
            {$items}
        </div>
        HTML;
    }

    private function renderByNumber(int $n): string
    {
        $items = Experience::query()->where('is_active', true)->orderBy('sort_order')->get();

        if ($n < 1 || $n > $items->count()) {
            $max = $items->count();

            return $this->renderError("Experience $n not found. Valid range: 1–$max.");
        }

        $html = $this->renderItem($items[$n - 1]);

        return <<<HTML
        <div class="t-block">
            {$this->header('experience ['.$n.']')}
            {$html}
        </div>
        HTML;
    }

    public function renderItem(Experience $exp): string
    {
        $badge = $exp->is_current ? '<span class="t-badge">current</span>' : '';
        $title = e($exp->title);
        $company = e($exp->company);
        $period = e($exp->period);
        $bullets = collect($exp->bullets ?? [])
            ->map(fn ($b) => '<li>'.e($b).'</li>')
            ->implode('');

        return <<<HTML
        <div class="t-exp-item">
            <div class="t-exp-header">
                <span class="t-accent">$title</span> <span class="t-dim">@</span> <span class="t-label">$company</span> $badge
                <span class="t-period">$period</span>
            </div>
            <ul class="t-bullets">$bullets</ul>
        </div>
        HTML;
    }
}
