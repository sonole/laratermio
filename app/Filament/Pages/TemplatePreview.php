<?php

namespace App\Filament\Pages;

use App\Services\TemplatePreviewService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\View;

class TemplatePreview extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::DocumentMagnifyingGlass;

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Template Preview';

    protected string $view = 'filament.pages.template-preview';

    public ?string $template = null;

    public string $previewHtml = '';

    public function mount(): void
    {
        $options = TemplatePreviewService::options();

        $this->template = ! empty($options) ? (string) array_key_first($options) : null;

        $this->form->fill([
            'template' => $this->template,
        ]);

        if ($this->template) {
            $this->renderPreview($this->template);
        }
    }

    protected function renderPreview(?string $template): void
    {
        if (blank($template)) {
            $this->previewHtml = $this->renderErrorHtml('Notice', 'No template selected.');
            return;
        }

        try {
            $viewName = TemplatePreviewService::view($template);

            if (! View::exists($viewName)) {
                $this->previewHtml = $this->renderErrorHtml(
                    'Warning',
                    "The view file [$viewName] does not exist."
                );
                return;
            }

            $this->previewHtml = View::make(
                $viewName,
                TemplatePreviewService::data($template)
            )->render();
        } catch (\Throwable $e) {
            $this->previewHtml = $this->renderErrorHtml(
                'Error generating preview',
                $e->getMessage()
            );
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('template')
                    ->label('Template')
                    ->options(TemplatePreviewService::options())
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (?string $state): void {
                        $this->template = $state;
                        $this->renderPreview($state);
                    }),
            ]);
    }

    /**
     * Helper to render unified error messages safely inside the isolated preview frame.
     */
    private function renderErrorHtml(string $label, string $message): string
    {
        return sprintf(
            '<div style="padding: 1.5rem; color: #ef4444; font-family: sans-serif; background: #fef2f2; border: 1px solid #fee2e2; border-radius: 0.5rem;">' .
            '<strong style="display:block; margin-bottom: 0.5rem;">%s</strong>' .
            '%s' .
            '</div>',
            e($label),
            e($message)
        );
    }
}
