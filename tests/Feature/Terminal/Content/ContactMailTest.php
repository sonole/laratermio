<?php

use App\Enums\SettingKey;
use App\Mail\ContactMessageConfirmationMail;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/** Prompt settings used in the mail header, plus the owner's name for the signature. */
function contactMailSettings(): void
{
    cvSetting(SettingKey::Name, 'Alex Example');
    cvSetting(SettingKey::PromptUsername, 'guest');
    cvSetting(SettingKey::PromptHostname, 'alex.example.com');
    cvSetting(SettingKey::PromptUsernameColor, '#111111');
    cvSetting(SettingKey::PromptHostnameColor, '#222222');
    cvSetting(SettingKey::PromptSeparatorColor, '#333333');
}

describe('ContactMessageMail', function () {
    it('names the sender in the subject', function () {
        $message = ContactMessage::factory()->create(['email' => 'jane@example.com']);

        $mail = new ContactMessageMail($message);

        $mail->assertHasSubject('New contact message from: jane@example.com');
        expect($mail->envelope()->subject)->toBe('New contact message from: jane@example.com');
    });

    it('renders the sender and the message', function () {
        $message = ContactMessage::factory()->create(['email' => 'jane@example.com', 'message' => 'Loved your portfolio']);

        $html = (new ContactMessageMail($message))->render();

        expect($html)->toContain('new contact message', 'jane@example.com', 'Loved your portfolio', 'Submitted via the');
    });

    it('escapes the sender and the message', function () {
        $message = ContactMessage::factory()->create(['email' => 'jane@example.com', 'message' => '<script>alert(1)</script> & more']);

        $html = (new ContactMessageMail($message))->render();

        expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt; &amp; more')->not->toContain('<script>');
    });

    it('uses the terminal prompt settings in the header and footer link', function () {
        contactMailSettings();
        $message = ContactMessage::factory()->create();

        $mail = new ContactMessageMail($message);
        $html = $mail->render();

        expect($html)->toContain('guest', '#111111', '#222222', '#333333', 'href="https://alex.example.com"')
            ->and($mail->getVariables())->toMatchArray(['username' => 'guest', 'hostname' => 'alex.example.com'])
            ->and($mail->getVariables()['hostnameDisplay'])->toBe('alex&zwnj;.example&zwnj;.com');
    });

    it('falls back to the default prompt when nothing is configured', function () {
        $message = ContactMessage::factory()->create();

        expect((new ContactMessageMail($message))->getVariables())
            ->toMatchArray(['username' => 'visitor', 'hostname' => 'localhost']);
    });

    it('has no recipient of its own and no attachments', function () {
        $mail = new ContactMessageMail(ContactMessage::factory()->create());

        expect($mail->to)->toBe([])
            ->and($mail->attachments())->toBe([])
            ->and($mail)->not->toBeInstanceOf(ShouldQueue::class);
    });

    it('can be addressed to the owner', function () {
        Mail::fake();
        $mail = new ContactMessageMail(ContactMessage::factory()->create());

        Mail::to('owner@example.com')->send($mail);

        Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $sent) => $sent->hasTo('owner@example.com'));
    });
});

describe('ContactMessageConfirmationMail', function () {
    it('has a fixed subject', function () {
        $mail = new ContactMessageConfirmationMail(ContactMessage::factory()->create());

        $mail->assertHasSubject('Got your email!');
        expect($mail->envelope()->subject)->toBe('Got your email!');
    });

    it('echoes the visitor message back and signs it with the owner name', function () {
        contactMailSettings();
        $message = ContactMessage::factory()->create(['message' => 'Let us work together']);

        $html = (new ContactMessageConfirmationMail($message))->render();

        expect($html)->toContain('message received', 'Let us work together', 'Alex Example', 'You received this because you used the');
    });

    it('escapes the visitor message', function () {
        $message = ContactMessage::factory()->create(['message' => '<script>alert(1)</script> & more']);

        $html = (new ContactMessageConfirmationMail($message))->render();

        expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt; &amp; more')->not->toContain('<script>');
    });

    it('uses the terminal prompt settings in the header and footer link', function () {
        contactMailSettings();
        $message = ContactMessage::factory()->create();

        $mail = new ContactMessageConfirmationMail($message);
        $html = $mail->render();

        expect($html)->toContain('guest', '#111111', '#222222', '#333333', 'href="https://alex.example.com"')
            ->and($mail->getVariables())->toMatchArray(['name' => 'Alex Example', 'username' => 'guest', 'hostname' => 'alex.example.com'])
            ->and($mail->getVariables()['hostnameDisplay'])->toBe('alex&zwnj;.example&zwnj;.com');
    });

    it('falls back to the default name and prompt when nothing is configured', function () {
        $message = ContactMessage::factory()->create();

        expect((new ContactMessageConfirmationMail($message))->getVariables())
            ->toMatchArray(['name' => 'Dev McDevface', 'username' => 'visitor', 'hostname' => 'localhost']);
    });

    it('has no recipient of its own and no attachments', function () {
        $mail = new ContactMessageConfirmationMail(ContactMessage::factory()->create());

        expect($mail->to)->toBe([])
            ->and($mail->attachments())->toBe([])
            ->and($mail)->not->toBeInstanceOf(ShouldQueue::class);
    });

    it('can be addressed to the visitor', function () {
        Mail::fake();
        $message = ContactMessage::factory()->create(['email' => 'jane@example.com']);

        Mail::to($message->email)->send(new ContactMessageConfirmationMail($message));

        Mail::assertSent(ContactMessageConfirmationMail::class, fn (ContactMessageConfirmationMail $sent) => $sent->hasTo('jane@example.com'));
    });
});
