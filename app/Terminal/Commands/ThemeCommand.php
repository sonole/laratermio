<?php

namespace App\Terminal\Commands;

use App\Terminal\TerminalResponse;

class ThemeCommand extends BaseCommand
{
    private const array VALID = ['light', 'dark', 'system'];

    public function name(): string
    {
        return 'theme';
    }

    public function helpGroup(): ?string
    {
        return 'system';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        if ($arg === null) {
            return TerminalResponse::echo($this->renderOptions());
        }

        if (! in_array($arg, self::VALID, true)) {
            return TerminalResponse::echo($this->renderUnknownOption($arg));
        }

        return TerminalResponse::theme($arg);
    }

    private function renderOptions(): string
    {
        $rows = implode('', array_map(
            fn ($o) => '<div class="t-help-row"><span class="t-cmd">'.e($o['option']).'</span><span class="t-dim">—</span><span>'.e($o['description']).'</span></div>',
            $this->helpOptions()
        ));

        return <<<HTML
        <div class="t-block">
            {$this->header('theme')}
            <div class="t-help-rows">$rows</div>
        </div>
        HTML;
    }
}
