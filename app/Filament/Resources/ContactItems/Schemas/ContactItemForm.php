<?php

namespace App\Filament\Resources\ContactItems\Schemas;

use App\Filament\Support\TranslationFields;
use App\Models\ContactItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Toggle::make('is_active')->label('Active')->default(true),
                        Select::make('icon')
                            ->options(self::iconOptions())
                            ->searchable()
                            ->allowHtml()
                            ->helperText('Search by name (e.g. "linkedin", "email")'),
                        TextInput::make('label')
                            ->required()
                            ->placeholder('alexandros@example.com'),
                        TextInput::make('url')
                            ->url()
                            ->placeholder('https://... (leave empty for plain text)')
                            ->helperText('Optional — makes the label a clickable link'),
                    ]),
                ...TranslationFields::make([
                    'label' => fn (string $name) => TextInput::make($name)
                        ->label('Label')
                        ->helperText('Translate text such as a city name; leave emails and phone numbers empty.'),
                ]),
            ]);
    }

    /** @return array<string, string> */
    private static function iconOptions(): array
    {
        $options = [];

        foreach (ContactItem::iconAliases() as $class => $alias) {
            $options[$class] = '<i class="'.$class.' fa-fw" style="margin-right: 0.35rem;"></i>'.$alias['label'];
        }

        return $options;
    }
}
