<?php

namespace App\Services;

use App\Contracts\TemplatePreview;
use App\Services\TemplatePreviews\ContactMessageConfirmationPreview;
use App\Services\TemplatePreviews\ContactMessagePreview;
use App\Services\TemplatePreviews\CvPreview;
use InvalidArgumentException;

class TemplatePreviewService
{
    /** @return array<string, TemplatePreview> */
    public static function templates(): array
    {
        return [
            'contact-message' => ContactMessagePreview::class,
            'contact-message-confirmation' => ContactMessageConfirmationPreview::class,
            'cv' => CvPreview::class,
        ];
    }

    public static function make(string $key): TemplatePreview
    {
        $class = static::templates()[$key] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("Unknown template preview [{$key}]");
        }

        return app($class);
    }

    public static function view(string $key): string
    {
        return static::make($key)->templatePreviewView();
    }

    public static function data(string $key): array
    {
        return static::make($key)->templatePreviewData();
    }

    public static function options(): array
    {
        $result = [];

        foreach (static::templates() as $key => $class) {
            $instance = app($class);
            $result[$key] = $instance->templatePreviewLabel();
        }

        return $result;
    }
}
