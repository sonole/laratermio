<?php

namespace App\Filament\Resources\Educations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class EducationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable()->width('48px'),
                TextColumn::make('title')->searchable()->sortable()->limit(35)->tooltip(fn ($record) => $record->title),
                TextColumn::make('institution')->searchable()->sortable()->color('gray')->limit(25)->tooltip(fn ($record) => $record->institution),
                TextColumn::make('period')->color('gray'),
                IconColumn::make('is_certification')->label('Cert')->boolean(),
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