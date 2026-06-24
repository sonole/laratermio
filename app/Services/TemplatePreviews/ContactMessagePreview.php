<?php

namespace App\Services\TemplatePreviews;

use App\Contracts\TemplatePreview;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;

class ContactMessagePreview implements TemplatePreview
{
    public function templatePreviewLabel(): string
    {
        return 'Contact Message';
    }

    public function templatePreviewView(): string
    {
        return 'mail.contact-message';
    }

    /** @return array<string, mixed> */
    public function templatePreviewData(): array
    {
        $contactMessage = new ContactMessage([
            'email' => 'visitor@mail.com',
            'message' => 'This is a test message.',
        ]);

        return new ContactMessageMail($contactMessage)
            ->getVariables();
    }
}
