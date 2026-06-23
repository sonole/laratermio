<?php

namespace App\Terminal\Commands;

use App\Terminal\TerminalResponse;

class SudoCommand extends BaseCommand
{
    public function name(): string
    {
        return 'sudo';
    }

    public function helpGroup(): ?string
    {
        return null;
    }

    protected function execute(?string $arg): TerminalResponse
    {
        return TerminalResponse::echo(
            '<span class="t-error">Permission denied.</span> <span class="t-dim">Nice try.</span>'
        );
    }
}
