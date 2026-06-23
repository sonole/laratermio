<?php

namespace App\Filament\Resources\Educations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EducationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_active')->label('Active')->default(true)->inline(false),
                            Toggle::make('is_certification')->label('Certification')->live()->inline(false),
                        ]),
                        TextInput::make('title')->required(),
                        TextInput::make('institution')->required(),
                        Grid::make()->schema([
                            DatePicker::make('start_date')
                                ->label(fn (Get $get) => $get('is_certification') ? 'Issued on' : 'Start date')
                                ->required()
                                ->native(false),
                            DatePicker::make('end_date')
                                ->native(false)
                                ->hidden(fn (Get $get) => (bool) $get('is_certification')),
                        ]),
                        TextInput::make('description')->label('Description')->placeholder('Major, department, notes…'),
                        TextInput::make('certificate_url')->label('Certificate URL')->url()->placeholder('https://…'),
                    ]),
            ]);
    }
}