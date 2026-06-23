<?php

namespace App\Filament\Pages;

use BackedEnum;
use Database\Seeders\ContentSeeder;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Config;
use UnitEnum;

class ImportDemoContent extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Import Content';

    protected static ?string $slug = 'import-demo-content';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.import-demo-content';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'settings' => true,
            'educations' => true,
            'experiences' => true,
            'projects' => true,
            'skills' => true,
            'contact' => true,
            'cv' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sections to import')
                    ->description('Each enabled section will have its table truncated and re-seeded with demo content. This cannot be undone.')
                    ->schema([
                        Toggle::make('settings')->label('Settings'),
                        Toggle::make('educations')->label('Educations'),
                        Toggle::make('experiences')->label('Experiences'),
                        Toggle::make('projects')->label('Projects'),
                        Toggle::make('skills')->label('Skills'),
                        Toggle::make('contact')->label('Contact items'),
                        Toggle::make('cv')->label('CV'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Import demo content')
                ->modalDescription('The selected tables will be truncated and replaced with demo content. Uploaded files for Settings and media for Projects will also be permanently deleted. This cannot be undone.')
                ->modalSubmitActionLabel('Import')
                ->action(function (): void {
                    $data = $this->form->getState();

                    foreach ($data as $section => $enabled) {
                        Config::set("app.seed_content.{$section}", $enabled);
                    }

                    app(ContentSeeder::class)->run();

                    Notification::make()
                        ->title('Demo content imported')
                        ->success()
                        ->send();
                }),
        ];
    }
}
