<?php

namespace App\Filament\Resources\ContactItems\Pages;

use App\Filament\Resources\ContactItems\ContactItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContactItem extends CreateRecord
{
    protected static string $resource = ContactItemResource::class;
}
