<?php

namespace App\Filament\Resources\NavItems\Tables;

use App\Enums\NavItemType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class NavItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable()->width('48px'),
                TextColumn::make('label')
                    ->getStateUsing(fn ($record): string => $record->getDisplayLabel())
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (NavItemType $state): string => $state->label())
                    ->color(fn (NavItemType $state): string => match ($state) {
                        NavItemType::Link => 'info',
                        NavItemType::Command => 'gray',
                        NavItemType::Cv => 'success',
                    }),
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
