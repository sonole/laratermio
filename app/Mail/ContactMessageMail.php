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

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ContactMessage $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New contact message from: '.$this->contactMessage->email);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-message',
            with: $this->getVariables(),
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function getVariables(): array
    {
        $hostname = Settings::getPromptHostname();

        return [
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
