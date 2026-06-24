<?php

namespace App\Terminal\Commands;

use App\Facades\Settings;
use App\Terminal\TerminalResponse;

class WhoamiCommand extends BaseCommand
{
    public function name(): string
    {
        return 'whoami';
    }

    public function helpGroup(): ?string
    {
        return 'system';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        if ($arg !== null) {
            return $this->responseUnknownOption($arg);
        }

        $username = e(Settings::getPromptUsername());

        return TerminalResponse::echo(<<<HTML
        <div class="t-block">
            <span class="t-accent">$username</span>
        </div>
        HTML);
    }
}
