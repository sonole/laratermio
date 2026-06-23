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
            'username' => Settings::get('prompt_username', 'visitor'),
            'hostname' => $hostname,
            'suffix' => Settings::get('prompt_suffix', ':~$'),
            'usernameColor' => Settings::get('prompt_username_color', '#4ade80'),
            'hostnameColor' => Settings::get('prompt_hostname_color', '#60a5fa'),
            'sepColor' => Settings::get('prompt_separator_color', '#6b7280'),
            'hostnameDisplay' => str_replace('.', '&zwnj;.', $hostname),
        ];
    }
}
