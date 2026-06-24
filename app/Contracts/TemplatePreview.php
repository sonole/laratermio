<?php

namespace App\Contracts;

interface TemplatePreview
{
    public function templatePreviewLabel(): string;

    public function templatePreviewView(): string;

    /** @return array<string, mixed> */
    public function templatePreviewData(): array;
}
