<?php

namespace App\Terminal\Concerns;

trait RendersHtml
{
    public function header(string $title): string
    {
        return '<p class="t-header">// '.e($title).'</p>';
    }

    protected function renderUnknownOption(string $arg): string
    {
        $safeArg = e($arg);

        return "<span class='t-error'>unknown option: <strong>$safeArg</strong> &mdash; type <span class='t-accent'>help</span> for usage.</span>";
    }

    protected function renderError(string $message): string
    {
        return "<span class='t-error'>$message</span>";
    }

    protected function renderNotFound(string $command): string
    {
        $cmd = e($command);

        return "<span class='t-error'>command not found: <strong>$cmd</strong> &mdash; type <span class='t-accent'>help</span> for available commands.</span>";
    }
}