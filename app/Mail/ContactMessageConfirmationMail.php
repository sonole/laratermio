<?php

namespace App\Mail;

use App\Facades\Settings;
use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ContactMessage $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Got your email!');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-message-confirmation',
            with: $this->getVariables(),
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }

    public function getVariables(): array
    {
        $hostname = Settings::getPromptHostname();

        return [
            'name' => Settings::getName(),
            'contactMessage' => $this->contactMessage,
            'username' => Settings::getPromptUsername(),
            'hostname' => $hostname,
            'suffix' => Settings::getPromptSuffix(),
            'usernameColor' => Settings::getPromptUsernameColor(),
            'hostnameColor' => Settings::getpromptHostnameColor(),
            'sepColor' => Settings::getpromptSeparatorColor(),
            'hostnameDisplay' => str_replace('.', '&zwnj;.', $hostname),
        ];
    }
}
