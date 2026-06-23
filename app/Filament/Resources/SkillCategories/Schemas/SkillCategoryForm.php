<?php

namespace App\Filament\Resources\SkillCategories\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SkillCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Toggle::make('is_active')->label('Active')->default(true),
                        TextInput::make('name')->required(),
                    ]),
                Section::make('Skills')
                    ->schema([
                        Repeater::make('items')
                            ->label('Skills')
                            ->simple(
                                TextInput::make('value')->required(),
                            )
                            ->reorderable()
                            ->addActionLabel('Add skill'),
                    ]),
            ]);
    }
}
