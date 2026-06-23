<?php

namespace App\Filament\Resources\TerminalCommands\Pages;

use App\Filament\Resources\TerminalCommands\TerminalCommandResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTerminalCommand extends EditRecord
{
    protected static string $resource = TerminalCommandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
