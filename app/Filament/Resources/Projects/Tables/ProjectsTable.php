<?php

namespace App\Filament\Resources\Projects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable()->width('48px'),
                SpatieMediaLibraryImageColumn::make('main_image')
                    ->label('Thumbnail')
                    ->collection('main_image')
                    ->imageHeight(48)
                    ->square(),
                TextColumn::make('name')->searchable()->sortable()->limit(40)->tooltip(fn ($record) => $record->name),
                TextColumn::make('subtitle')->color('gray')->limit(30)->tooltip(fn ($record) => $record->subtitle),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
