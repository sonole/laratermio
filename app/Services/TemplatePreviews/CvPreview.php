<?php

namespace App\Services\TemplatePreviews;

use App\Contracts\TemplatePreview;
use App\Services\CvService;

class CvPreview implements TemplatePreview
{
    public function templatePreviewLabel(): string
    {
        return 'CV';
    }

    public function templatePreviewView(): string
    {
        return 'cv';
    }

    public function templatePreviewData(): array
    {
        return CvService::getVariables();
    }
}
