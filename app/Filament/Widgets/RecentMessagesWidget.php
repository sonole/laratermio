<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentMessagesWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = '3';

    protected static ?string $heading = 'Recent Messages';

    public function table(Table $table): Table
    {
        return $table
            ->query(ContactMessage::query()->latest()->limit(5))
            ->columns([
                TextColumn::make('email')
                    ->copyable(),
                TextColumn::make('message')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
