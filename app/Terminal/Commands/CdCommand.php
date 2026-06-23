<?php

namespace App\Terminal\Commands;

use App\Facades\Settings;
use App\Terminal\TerminalContext;
use App\Terminal\TerminalResponse;

class CdCommand extends BaseCommand
{
    public function __construct(private readonly TerminalContext $context) {}

    public function name(): string
    {
        return 'cd';
    }

    public function helpGroup(): ?string
    {
        return 'system';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        if ($arg === null || $arg === '' || $arg === '~') {
            return $this->navigateTo('~');
        }

        if ($arg === '..') {
            if ($this->context->getCwd() === '~') {
                return TerminalResponse::echo($this->renderError('already at home directory.'));
            }

            return $this->navigateTo('~');
        }

        $target = str_starts_with($arg, '~/') ? substr($arg, 2) : ltrim($arg, '/');

        if (in_array($target, TerminalContext::FILESYSTEM_ROOTS, true)) {
            return $this->navigateTo('~/'.$target);
        }

        return TerminalResponse::echo($this->renderError('cd: '.e($arg).': no such directory'));
    }

    private function navigateTo(string $cwd): TerminalResponse
    {
        $this->context->setCwd($cwd);

        return TerminalResponse::cd(Settings::getPromptWithCwd($cwd, true), $cwd);
    }
}
