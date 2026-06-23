<?php

namespace App\Terminal\Commands;

use App\Models\TerminalCommand;
use App\Terminal\Concerns\RendersHtml;
use App\Terminal\Contracts\TerminalCommandContract;
use App\Terminal\TerminalResponse;

abstract class BaseCommand implements TerminalCommandContract
{
    use RendersHtml;

    final public function handle(?string $arg): TerminalResponse
    {
        if (in_array($arg, ['-h', '--help'], true)) {
            return TerminalResponse::echo($this->renderCommandHelp());
        }

        return $this->execute($arg);
    }

    abstract protected function execute(?string $arg): TerminalResponse;

    public function helpGroup(): ?string
    {
        return 'explore';
    }

    /**
     * @return array<int, array{option: string, description: string}>
     */
    public function helpOptions(): array
    {
        return [];
    }

    protected function renderCommandHelp(): string
    {
        $record = TerminalCommand::query()->where('name', $this->name())->first();
        $desc = $record?->description
            ? '<p class="t-paragraph">'.e($record->description).'</p>'
            : '';

        $optionRows = $this->helpOptions();
        $optionsHtml = '';

        if (! empty($optionRows)) {
            $rows = implode('', array_map(
                fn ($o) => '<div class="t-help-row"><span class="t-cmd">'.e($o['option']).'</span><span class="t-dim">—</span><span>'.e($o['description']).'</span></div>',
                $optionRows
            ));
            $optionsHtml = '<div class="t-help-rows">'.$rows.'</div>';
        }

        return <<<HTML
        <div class="t-block">
            {$this->header($this->name().' --help')}
            $desc
            $optionsHtml
        </div>
        HTML;
    }

    protected function responseUnknownOption(string $arg): TerminalResponse
    {
        return TerminalResponse::echo($this->renderUnknownOption($arg));
    }
}
