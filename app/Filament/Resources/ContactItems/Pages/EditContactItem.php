<?php

namespace App\Filament\Resources\ContactItems\Pages;

use App\Filament\Resources\ContactItems\ContactItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContactItem extends EditRecord
{
    protected static string $resource = ContactItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
