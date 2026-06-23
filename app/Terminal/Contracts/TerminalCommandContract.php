<?php

namespace App\Terminal\Contracts;

use App\Terminal\TerminalResponse;

interface TerminalCommandContract
{
    public function name(): string;

    public function handle(?string $arg): TerminalResponse;

    /** Group name shown in `help` output. Return null to exclude from help. */
    public function helpGroup(): ?string;
}