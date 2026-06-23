<?php

namespace App\Filament\Resources\TerminalCommands\Pages;

use App\Filament\Resources\TerminalCommands\TerminalCommandResource;
use Filament\Resources\Pages\ListRecords;

class ListTerminalCommands extends ListRecords
{
    protected static string $resource = TerminalCommandResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
