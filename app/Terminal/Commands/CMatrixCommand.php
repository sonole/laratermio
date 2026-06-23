<?php

namespace App\Terminal\Commands;

use App\Terminal\TerminalResponse;

class CMatrixCommand extends BaseCommand
{
    public function name(): string
    {
        return 'cmatrix';
    }

    public function helpGroup(): ?string
    {
        return null;
    }

    protected function execute(?string $arg): TerminalResponse
    {
        return TerminalResponse::overlay('cmatrix');
    }
}
