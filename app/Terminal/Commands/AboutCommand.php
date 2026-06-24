<?php

namespace App\Terminal\Commands;

use App\Facades\Settings;
use App\Terminal\TerminalResponse;

class AboutCommand extends BaseCommand
{
    public function name(): string
    {
        return 'about';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        if ($arg !== null) {
            return $this->responseUnknownOption($arg);
        }

        $text = e(Settings::getAbout());

        if (blank($text)) {
            return TerminalResponse::echo($this->renderError('about text not found.'));
        }

        $name = e(Settings::getName());
        $role = e(Settings::getRole());

        return TerminalResponse::echo(<<<HTML
        <div class="t-block">
            {$this->header('about')}
            <p. class="t-paragraph">$text</p.>
            <p class="t-dim t-mt">$name &mdash; $role</p>
        </div>
        HTML);
    }
}
