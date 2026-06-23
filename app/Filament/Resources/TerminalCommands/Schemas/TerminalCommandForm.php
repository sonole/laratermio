<?php

namespace App\Filament\Resources\TerminalCommands\Schemas;

use App\Enums\InteractionType;
use App\Terminal\Contracts\HasStructuredData;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TerminalCommandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Toggle::make('is_enabled')->label('Enabled')->default(true),
                        Grid::make()->schema([
                            TextInput::make('name')
                                ->required()
                                ->placeholder('about'),
                            TextInput::make('display_label')
                                ->required()
                                ->placeholder('about'),
                        ]),
                        TextInput::make('command_class')
                            ->required()
                            ->label('Handler class')
                            ->placeholder('App\Terminal\Commands\AboutCommand')
                            ->helperText('Fully qualified class name implementing TerminalCommandContract'),
                        Select::make('interaction_type')
                            ->label('Interaction type')
                            ->options(collect(InteractionType::cases())->mapWithKeys(
                                fn (InteractionType $type) => [$type->value => $type->label()]
                            ))
                            ->visible(fn ($get) => is_a($get('command_class') ?? '', HasStructuredData::class, true)),
                        TextInput::make('description')
                            ->placeholder('Who I am and what drives me'),
                    ]),
            ]);
    }
}