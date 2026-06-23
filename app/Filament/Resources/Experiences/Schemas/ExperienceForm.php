<?php

namespace App\Filament\Resources\Experiences\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ExperienceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_active')->label('Active')->default(true)->inline(false),
                            Toggle::make('is_current')->label('Current role')->live()->inline(false),
                        ]),
                        TextInput::make('title')->required(),
                        TextInput::make('company')->required(),
                        Grid::make()->schema([
                            DatePicker::make('start_date')->required()->native(false),
                            DatePicker::make('end_date')->native(false)->hidden(fn (Get $get) => (bool) $get('is_current')),
                        ]),
                    ]),
                Section::make('Bullets')
                    ->schema([
                        Repeater::make('bullets')
                            ->label('Bullet points')
                            ->simple(
                                Textarea::make('value')->required()->rows(4),
                            )
                            ->reorderable()
                            ->addActionLabel('Add bullet'),
                    ]),
            ]);
    }
}
