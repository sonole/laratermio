<?php

namespace App\Filament\Resources\NavItems\Schemas;

use App\Enums\NavItemType;
use App\Models\NavItem;
use App\Models\TerminalCommand;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class NavItemForm
{
    public static function configure(Schema $schema): Schema
    {
        $isLink = fn ($get) => $get('type') === NavItemType::Link->value;
        $isCv = fn ($get) => $get('type') === NavItemType::Cv->value;
        $isLinkOrCv = fn ($get) => in_array($get('type'), [NavItemType::Link->value, NavItemType::Cv->value]);

        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make()->schema([
                            Toggle::make('is_active')->label('Active')->default(true)->inline(false),
                            TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
                        ]),
                        Select::make('type')
                            ->options(collect(NavItemType::cases())->mapWithKeys(
                                fn (NavItemType $t) => [$t->value => $t->label()]
                            ))
                            ->default(NavItemType::Command->value)
                            ->required()
                            ->live(),
                        Select::make('terminal_command_id')
                            ->label('Command')
                            ->options(
                                TerminalCommand::query()->pluck('display_label', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->visible(fn ($get) => $get('type') === NavItemType::Command->value),
                        TextInput::make('command_args')
                            ->label('Arguments')
                            ->placeholder('-a')
                            ->helperText('Optional arguments appended to the command when the nav button is clicked.')
                            ->visible(fn ($get) => $get('type') === NavItemType::Command->value),
                        TextInput::make('label')
                            ->required(fn ($get) => ! $isCv($get))
                            ->placeholder(fn ($get) => $isCv($get) ? 'cv' : null)
                            ->helperText(fn ($get) => $isCv($get) ? 'Defaults to "cv" if left empty.' : null)
                            ->visible($isLinkOrCv),
                        Select::make('url_source')
                            ->label('URL source')
                            ->options(['external' => 'External URL', 'file' => 'Upload a file'])
                            ->default('external')
                            ->live()
                            ->dehydrated(false)
                            ->visible($isLink)
                            ->afterStateHydrated(function ($set, $get) {
                                $url = $get('url');
                                $set('url_source', ($url && str_starts_with($url, '/storage/')) ? 'file' : 'external');
                            }),
                        TextInput::make('url')
                            ->placeholder('https://...')
                            // Hidden while a file is the source, but it still has to save the link to that file.
                            ->dehydratedWhenHidden(fn ($get) => $isLink($get))
                            ->dehydrateStateUsing(function ($state, $get) {
                                if ($get('url_source') !== 'file') {
                                    return $state;
                                }

                                // By now the upload has been stored, so its state holds the path on the disk.
                                $stored = Arr::first(Arr::wrap($get('url_file')));

                                return is_string($stored) && $stored !== '' ? '/storage/'.$stored : null;
                            })
                            ->visible(fn ($get) => $isLink($get) && $get('url_source') !== 'file'),
                        FileUpload::make('url_file')
                            ->label('File')
                            ->disk('public')
                            ->directory(NavItem::UPLOAD_DIRECTORY)
                            ->dehydrated(false)
                            ->visible(fn ($get) => $isLink($get) && $get('url_source') === 'file')
                            ->afterStateHydrated(function ($set, $get) {
                                $url = $get('url');
                                if ($url && str_starts_with($url, '/storage/')) {
                                    $set('url_file', ltrim(str_replace('/storage/', '', $url), '/'));
                                }
                            }),
                        Select::make('target')
                            ->required()
                            ->options(['_self' => 'Same tab', '_blank' => 'New tab'])
                            ->default('_blank')
                            ->visible($isLinkOrCv),
                    ]),
            ]);
    }
}
