<?php

namespace App\Terminal\Commands;

use App\Terminal\TerminalResponse;

class HistoryCommand extends BaseCommand
{
    public function name(): string
    {
        return 'history';
    }

    public function helpGroup(): ?string
    {
        return 'system';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        return TerminalResponse::clientHistory();
    }
}
