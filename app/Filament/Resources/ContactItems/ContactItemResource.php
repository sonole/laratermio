<?php

namespace App\Filament\Resources\ContactItems;

use App\Filament\Resources\ContactItems\Pages\CreateContactItem;
use App\Filament\Resources\ContactItems\Pages\EditContactItem;
use App\Filament\Resources\ContactItems\Pages\ListContactItems;
use App\Filament\Resources\ContactItems\Schemas\ContactItemForm;
use App\Filament\Resources\ContactItems\Tables\ContactItemsTable;
use App\Models\ContactItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContactItemResource extends Resource
{
    protected static ?string $model = ContactItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return ContactItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactItems::route('/'),
            'create' => CreateContactItem::route('/create'),
            'edit' => EditContactItem::route('/{record}/edit'),
        ];
    }
}
