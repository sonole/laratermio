<?php

namespace App\Services\TemplatePreviews;

use App\Contracts\TemplatePreview;
use App\Mail\ContactMessageConfirmationMail;
use App\Models\ContactMessage;

class ContactMessageConfirmationPreview implements TemplatePreview
{
    public function templatePreviewLabel(): string
    {
        return 'Contact Message Confirmation';
    }

    public function templatePreviewView(): string
    {
        return 'mail.contact-message-confirmation';
    }

    public function templatePreviewData(): array
    {
        $contactMessage = new ContactMessage([
            'email' => 'visitor@mail.com',
            'message' => 'This is a test message.',
        ]);

        return new ContactMessageConfirmationMail($contactMessage)
            ->getVariables();
    }
}
