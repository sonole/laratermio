<?php

namespace App\Terminal\Commands;

use App\Terminal\TerminalResponse;

class ClearCommand extends BaseCommand
{
    public function name(): string
    {
        return 'clear';
    }

    public function helpGroup(): ?string
    {
        return 'system';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        return $arg !== null
            ? $this->responseUnknownOption($arg)
            : TerminalResponse::clear();
    }
}
