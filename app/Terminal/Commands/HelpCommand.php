<?php

namespace App\Terminal\Commands;

use App\Models\TerminalCommand;
use App\Terminal\TerminalResponse;

class HelpCommand extends BaseCommand
{
    public function name(): string
    {
        return 'help';
    }

    public function helpGroup(): ?string
    {
        return null;
    }

    protected function execute(?string $arg): TerminalResponse
    {
        if ($arg !== null) {
            return $this->responseUnknownOption($arg);
        }

        $records = TerminalCommand::active()
            ->orderBy('name')
            ->get();

        $groupOrder = ['explore', 'experience', 'projects', 'system'];
        $groups = [];

        foreach ($records as $record) {
            if (! class_exists($record->command_class)) {
                continue;
            }

            $command = app($record->command_class);
            $group = $command->helpGroup();

            if ($group === null) {
                continue;
            }

            $groups[$group][] = ['record' => $record, 'command' => $command];
        }

        uksort($groups, fn (string $a, string $b) => (array_search($a, $groupOrder)) <=> (array_search($b, $groupOrder)));

        $sectionsHtml = '';

        foreach ($groups as $groupName => $entries) {
            $rows = '';

            foreach ($entries as ['record' => $record, 'command' => $command]) {
                $label = e($record->display_label);
                $desc = e($record->description ?? '');
                $rows .= "<div class=\"t-help-row\"><span class=\"t-cmd\">$label</span><span class=\"t-dim\">—</span><span>$desc</span></div>";

                foreach ($command->helpOptions() as $option) {
                    $opt = e($option['option']);
                    $optDesc = e($option['description']);
                    $rows .= "<div class=\"t-help-row\"><span class=\"t-cmd\">$opt</span><span class=\"t-dim\">—</span><span>$optDesc</span></div>";
                }
            }

            $group = e($groupName);
            $sectionsHtml .= <<<HTML
            <div class="t-help-section">
                <p class="t-help-group">$group</p>
                <div class="t-help-rows">$rows</div>
            </div>
            HTML;
        }

        $header = $this->header('help');

        return TerminalResponse::echo(<<<HTML
        <div class="t-block">
            $header
            $sectionsHtml
        </div>
        HTML);
    }
}
