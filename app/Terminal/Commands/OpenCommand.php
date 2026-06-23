<?php

namespace App\Terminal\Commands;

use App\Models\ContactItem;
use App\Terminal\TerminalResponse;
use Illuminate\Database\Eloquent\Collection;

class OpenCommand extends BaseCommand
{
    public function name(): string
    {
        return 'open';
    }

    public function helpGroup(): ?string
    {
        return 'explore';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        $items = ContactItem::query()
            ->where('is_active', true)
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->orderBy('sort_order')
            ->get();

        if ($arg === null) {
            return TerminalResponse::echo($this->renderList($items));
        }

        $needle = strtolower(trim($arg));

        $match = $items->first(function (ContactItem $item) use ($needle) {
            if (stripos($item->label, $needle) !== false) {
                return true;
            }

            $alias = $item->iconAlias();

            return $alias !== null && str_contains($alias, $needle);
        });

        if ($match === null && $needle === 'admin') {
            return TerminalResponse::open(route('filament.admin.pages.dashboard'));
        }

        if ($match === null) {
            $safe = e($arg);

            return TerminalResponse::echo($this->renderError("No link found matching <strong>$safe</strong> — type <span class='t-accent'>open</span> to see available links."));
        }

        return TerminalResponse::open($match->url);
    }

    /** @param Collection<int, ContactItem> $items */
    private function renderList(Collection $items): string
    {
        $rows = $items->map(function (ContactItem $item) {
            $raw = $item->icon ?? '·';
            $icon = str_starts_with($raw, 'fa')
                ? '<i class="'.e($raw).' fa-fw t-dim"></i>'
                : '<span class="t-dim">'.e($raw).'</span>';

            $alias = $item->iconAlias();
            $hint = $alias !== null
                ? '<span class="t-dim"> (open '.e($alias).')</span>'
                : '';

            $label = e($item->label);

            return "<div class=\"t-help-row\">$icon<span class=\"t-link\">$label</span>$hint</div>";
        })->implode('');

        return <<<HTML
        <div class="t-block">
            {$this->header('open')}
            <div class="t-help-rows">$rows</div>
            <p class="t-dim t-mt">Usage: open &lt;alias or label&gt;</p>
        </div>
        HTML;
    }
}
