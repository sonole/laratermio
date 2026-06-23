<?php

namespace App\Filament\Widgets;

use App\Services\CvService;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

class CvWidget extends Widget
{
    protected string $view = 'filament.widgets.cv-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    public bool $cvExists = false;

    public ?string $lastGeneratedAt = null;

    public function mount(CvService $cvService): void
    {
        $this->cvExists = $cvService->exists();
        $this->lastGeneratedAt = $cvService->lastGeneratedAt()?->diffForHumans();
    }

    public function generate(CvService $cvService): void
    {
        $cvService->generate();

        $this->cvExists = true;
        $this->lastGeneratedAt = $cvService->lastGeneratedAt()?->diffForHumans();

        Notification::make()
            ->title('CV generated successfully')
            ->icon(Heroicon::OutlinedDocumentCheck)
            ->success()
            ->send();
    }

    public function cvUrl(): string
    {
        return route('cv');
    }
}