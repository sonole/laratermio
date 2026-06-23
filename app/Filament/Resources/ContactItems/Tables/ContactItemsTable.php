<?php

namespace App\Filament\Resources\ContactItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ContactItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable()->width('48px'),
                TextColumn::make('icon')
                    ->width('48px')
                    ->formatStateUsing(fn ($state) => new HtmlString(
                        str_starts_with($state ?? '', 'fa')
                            ? '<i class="'.$state.' text-lg"></i>'
                            : e($state ?? '')
                    )),
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('url')->color('gray')->limit(50)->placeholder('—'),
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
