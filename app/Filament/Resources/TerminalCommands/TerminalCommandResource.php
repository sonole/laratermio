<?php

namespace App\Filament\Resources\TerminalCommands;

use App\Filament\Resources\TerminalCommands\Pages\EditTerminalCommand;
use App\Filament\Resources\TerminalCommands\Pages\ListTerminalCommands;
use App\Filament\Resources\TerminalCommands\Schemas\TerminalCommandForm;
use App\Filament\Resources\TerminalCommands\Tables\TerminalCommandsTable;
use App\Models\TerminalCommand;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TerminalCommandResource extends Resource
{
    protected static ?string $model = TerminalCommand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?string $navigationLabel = 'Commands';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return TerminalCommandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TerminalCommandsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTerminalCommands::route('/'),
            'edit' => EditTerminalCommand::route('/{record}/edit'),
        ];
    }
}
