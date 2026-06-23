<?php

namespace App\Terminal;

use App\Models\TerminalCommand;
use App\Terminal\Concerns\RendersHtml;
use App\Terminal\Contracts\TerminalCommandContract;

class CommandRegistry
{
    use RendersHtml;

    public function resolve(string $name): ?TerminalCommandContract
    {
        $record = TerminalCommand::query()->where('name', $name)->first();

        if (! $record || ! $record->is_enabled) {
            return null;
        }

        return $this->instantiate($record->command_class);
    }

    public function dispatch(string $name, ?string $arg): ?TerminalResponse
    {
        $record = TerminalCommand::query()->where('name', $name)->first();

        if (! $record) {
            return null;
        }

        if (! $record->is_enabled) {
            return TerminalResponse::echo($this->renderNotFound($name));
        }

        $command = $this->instantiate($record->command_class);

        if ($command === null) {
            return null;
        }

        return $command->handle($arg);
    }

    private function instantiate(?string $class): ?TerminalCommandContract
    {
        if (! $class || ! class_exists($class)) {
            return null;
        }

        return app($class);
    }
}