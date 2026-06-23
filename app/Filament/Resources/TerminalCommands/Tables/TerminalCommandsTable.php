<?php

namespace App\Filament\Resources\TerminalCommands\Tables;

use App\Enums\InteractionType;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TerminalCommandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('name')->badge()->color('gray'),
                TextColumn::make('display_label')->label('Label'),
                TextColumn::make('description')->color('gray')->limit(50)->placeholder('—'),
                TextColumn::make('interaction_type')
                    ->label('Interaction')
                    ->badge()
                    ->formatStateUsing(fn (?InteractionType $state): string => $state?->label() ?? '')
                    ->color(fn (?InteractionType $state): string => match ($state) {
                        InteractionType::Paginate => 'info',
                        InteractionType::Selector => 'warning',
                        default                   => 'gray',
                    })
                    ->placeholder('default'),
                ToggleColumn::make('is_enabled')->label('Enabled'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
