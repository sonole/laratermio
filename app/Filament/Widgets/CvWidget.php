<?php

namespace App\Filament\Widgets;

use App\Services\CvLocales;
use App\Services\CvService;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /** Generate the public CV, which is always in the main language. */
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

    /**
     * Render the CV in another language and send it to this browser. Nothing is stored, so
     * only the main-language CV is ever reachable by visitors.
     */
    public function download(string $locale, CvService $cvService, CvLocales $locales): StreamedResponse
    {
        abort_unless(in_array($locale, $locales->extra(), true), 404);

        $pdf = $cvService->render($locale);

        return response()->streamDownload(
            fn () => print $pdf,
            $cvService->filename($locale),
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * Languages besides the main one that the CV can be downloaded in.
     *
     * @return array<string, string> locale => native name
     */
    public function extraLanguages(): array
    {
        $locales = app(CvLocales::class);

        return collect($locales->extra())
            ->mapWithKeys(fn (string $locale) => [$locale => $locales->name($locale)])
            ->all();
    }

    public function baseLanguage(): string
    {
        $locales = app(CvLocales::class);

        return $locales->name($locales->base());
    }

    public function cvUrl(): string
    {
        return route('cv');
    }
}
