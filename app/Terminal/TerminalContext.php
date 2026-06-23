<?php

namespace App\Terminal;

class TerminalContext
{
    /** @var string[] */
    public const array FILESYSTEM_ROOTS = ['projects', 'skills', 'experience', 'education', 'contact'];

    private string $cwd = '~';

    public function getCwd(): string
    {
        return $this->cwd;
    }

    public function setCwd(string $cwd): void
    {
        $this->cwd = $cwd;
    }
}
