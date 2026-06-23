<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->width('48px'),
                TextColumn::make('email')->searchable()->sortable()->copyable(),
                TextColumn::make('message')->searchable()->limit(60)->placeholder('—')->wrap(),
                TextColumn::make('visitor_status')->label('Visitor')->badge()->sortable(),
                TextColumn::make('admin_status')->label('Admin')->badge()->sortable(),
                TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable()->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
