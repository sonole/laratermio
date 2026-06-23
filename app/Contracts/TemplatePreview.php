<?php

namespace App\Contracts;

interface TemplatePreview
{
    public function templatePreviewLabel(): string;

    public function templatePreviewView(): string;

    public function templatePreviewData(): array;
}
