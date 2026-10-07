<?php

use App\Enums\ContactMessageStatus;
use App\Enums\SettingKey;
use App\Mail\ContactMessageConfirmationMail;
use App\Mail\ContactMessageMail;
use App\Models\ContactItem;
use App\Models\ContactMessage;
use App\Models\TerminalCommand;
use App\Models\User;
use App\Terminal\Commands\ContactCommand;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

function contactCommandRun(?string $arg = null): string
{
    return app(ContactCommand::class)->handle($arg)->html;
}

/** The site owner the admin notification is addressed to (matched on `app.admin.email`). */
function contactCommandOwner(): User
{
    return User::factory()->create(['email' => config('app.admin.email')]);
}

/**
 * Make the real (array) mailer throw for every message whose subject starts with `$subjectPrefix`.
 * Returns the subjects the mailer was asked to send, in order, including the ones that failed.
 */
function contactCommandBreakMail(string $subjectPrefix): ArrayObject
{
    $attempts = new ArrayObject;

    Event::listen(MessageSending::class, function (MessageSending $event) use ($attempts, $subjectPrefix) {
        $subject = (string) $event->message->getSubject();
        $attempts[] = $subject;

        if (str_starts_with($subject, $subjectPrefix)) {
            throw new RuntimeException('mail transport down');
        }
    });

    return $attempts;
}

describe('contact command', function () {
    describe('contact card', function () {
        it('reports that there are no entries', function () {
            $response = app(ContactCommand::class)->handle(null);

            expect($response->type)->toBe('echo')
                ->and($response->html)->toContain('no contact entries found.');
        });

        it('ignores inactive items', function () {
            ContactItem::factory()->inactive()->create(['label' => 'hidden@example.com']);

            expect(contactCommandRun())->toContain('no contact entries found.')->not->toContain('hidden@example.com');
        });

        it('lists active items in sort order under the name and role', function () {
            cvSetting(SettingKey::Name, 'Alex Example');
            cvSetting(SettingKey::Role, 'Software Engineer');
            ContactItem::factory()->create(['label' => 'second-item', 'sort_order' => 2]);
            ContactItem::factory()->create(['label' => 'first-item', 'sort_order' => 1]);
            ContactItem::factory()->inactive()->create(['label' => 'hidden-item', 'sort_order' => 0]);

            $html = contactCommandRun();

            expect($html)->toContain('// contact', 'Alex Example', 'Software Engineer', 'first-item', 'second-item')
                ->not->toContain('hidden-item')
                ->and(strpos($html, 'Alex Example'))->toBeLessThan(strpos($html, 'first-item'))
                ->and(strpos($html, 'first-item'))->toBeLessThan(strpos($html, 'second-item'));
        });

        it('renders a Font Awesome icon as an icon element', function () {
            ContactItem::factory()->create(['icon' => 'fa-brands fa-github']);

            expect(contactCommandRun())->toContain('<i class="fa-brands fa-github fa-fw"></i>');
        });

        it('renders any other icon as plain text', function () {
            ContactItem::factory()->create(['icon' => '@']);

            expect(contactCommandRun())->toContain('<span class="t-contact-icon">@</span>')->not->toContain('<i class=');
        });

        it('falls back to a dot when the icon is empty', function () {
            ContactItem::factory()->create(['icon' => null]);

            expect(contactCommandRun())->toContain('<span class="t-contact-icon">·</span>');
        });

        it('links the label when the item has a URL', function () {
            ContactItem::factory()->create(['label' => 'me@example.com', 'url' => 'mailto:me@example.com']);

            expect(contactCommandRun())
                ->toContain('<a class="t-link" href="mailto:me@example.com" target="_blank">me@example.com</a>');
        });

        it('shows a plain label when the item has no URL', function () {
            ContactItem::factory()->create(['label' => 'Athens, Greece', 'url' => null]);

            expect(contactCommandRun())->toContain('<span>Athens, Greece</span>')->not->toContain('<a class="t-link"');
        });

        it('escapes names, roles, labels, icons and URLs', function () {
            cvSetting(SettingKey::Name, '<script>alert(1)</script>');
            cvSetting(SettingKey::Role, 'Dev & <b>Ops</b>');
            ContactItem::factory()->create(['icon' => 'fa-solid"><script>x</script>', 'label' => '<i>label</i>', 'url' => 'https://example.com/?a=1&b=2"><u>']);

            expect(contactCommandRun())
                ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;', 'Dev &amp; &lt;b&gt;Ops&lt;/b&gt;', '&lt;i&gt;label&lt;/i&gt;', 'a=1&amp;b=2&quot;&gt;&lt;u&gt;')
                ->not->toContain('<script>', '<b>Ops', '<i>label', '<u>');
        });

        it('documents the submit syntax in its help', function () {
            TerminalCommand::factory()->create([
                'name' => 'contact',
                'command_class' => ContactCommand::class,
                'description' => 'Get in touch',
            ]);

            expect(contactCommandRun('--help'))->toContain('// contact --help', 'Get in touch', 'contact &lt;email&gt; &lt;message&gt;');
        });
    });

    describe('submitting a message', function () {
        beforeEach(function () {
            Mail::fake();
        });

        it('stores the message and confirms to the visitor', function () {
            $html = contactCommandRun('jane@example.com Hello there, nice portfolio');

            $message = ContactMessage::query()->sole();

            expect($html)->toContain('// contact', 'Got it', 'jane@example.com', "I'll reach out soon.")
                ->and($message->email)->toBe('jane@example.com')
                ->and($message->message)->toBe('Hello there, nice portfolio');
        });

        it('lowercases the email address', function () {
            contactCommandRun('Jane.Doe@Example.COM Hi');

            expect(ContactMessage::query()->sole()->email)->toBe('jane.doe@example.com');
        });

        it('strips wrapping quotes and whitespace from the message', function (string $arg) {
            contactCommandRun($arg);

            expect(ContactMessage::query()->sole()->message)->toBe('hello world');
        })->with([
            'double quotes' => 'jane@example.com "hello world"',
            'single quotes' => "jane@example.com 'hello world'",
            'padding' => 'jane@example.com    hello world   ',
        ]);

        it('keeps the inner spacing of a multi-word message', function () {
            contactCommandRun('jane@example.com one  two   three');

            expect(ContactMessage::query()->sole()->message)->toBe('one  two   three');
        });

        it('accepts a message of exactly 255 characters', function () {
            contactCommandRun('jane@example.com '.str_repeat('a', 255));

            expect(ContactMessage::query()->sole()->message)->toHaveLength(255);
        });

        it('sends the confirmation to the visitor', function () {
            contactCommandRun('jane@example.com Hello');

            Mail::assertSent(ContactMessageConfirmationMail::class, 1);
            Mail::assertSent(ContactMessageConfirmationMail::class, fn (ContactMessageConfirmationMail $mail) => $mail->hasTo('jane@example.com')
                && $mail->contactMessage->is(ContactMessage::query()->sole()));
        });

        it('notifies the site owner', function () {
            $owner = contactCommandOwner();

            contactCommandRun('jane@example.com Hello');

            Mail::assertSent(ContactMessageMail::class, 1);
            Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail) => $mail->hasTo($owner->email)
                && $mail->contactMessage->message === 'Hello');
        });

        it('marks both deliveries as sent', function () {
            contactCommandOwner();

            contactCommandRun('jane@example.com Hello');

            $message = ContactMessage::query()->sole();

            expect($message->visitor_status)->toBe(ContactMessageStatus::Sent)
                ->and($message->admin_status)->toBe(ContactMessageStatus::Sent);
        });

        it('does not send an owner notification when no owner account exists', function () {
            $html = contactCommandRun('jane@example.com Hello');

            Mail::assertSent(ContactMessageConfirmationMail::class, 1);
            Mail::assertNotSent(ContactMessageMail::class);
            expect($html)->toContain('Got it');
        });

        it('does not mark the owner notification as sent when it was never sent', function () {
            contactCommandRun('jane@example.com Hello');

            expect(ContactMessage::query()->sole()->admin_status)->not->toBe(ContactMessageStatus::Sent);
        });

        it('escapes the email it confirms back', function () {
            $html = contactCommandRun('"quoted"@example.com Hello');

            expect($html)->toContain('&quot;quoted&quot;@example.com')->not->toContain('"quoted"@example.com');
        });
    });

    describe('validation', function () {
        beforeEach(function () {
            Mail::fake();
        });

        it('rejects an invalid email address', function (string $arg) {
            $html = contactCommandRun($arg);

            expect($html)->toContain('invalid email address:')
                ->and(ContactMessage::query()->count())->toBe(0);
            Mail::assertNothingSent();
        })->with([
            'not an email' => 'foo Hello',
            'missing domain' => 'jane@ Hello',
            'spaces' => 'jane@@example.com Hello',
        ]);

        it('echoes the rejected email escaped', function () {
            $html = contactCommandRun('<script>alert(1)</script> Hello');

            expect($html)->toContain('<strong>&lt;script&gt;alert(1)&lt;/script&gt;</strong>')->not->toContain('<script>');
        });

        it('reports the email problem before the missing message', function () {
            expect(contactCommandRun('foo'))->toContain('invalid email address:');
        });

        it('shows the usage when the message is missing', function (string $arg) {
            $html = contactCommandRun($arg);

            expect($html)->toContain('usage: contact &lt;email&gt; &quot;&lt;message&gt;&quot;')
                ->and(ContactMessage::query()->count())->toBe(0);
            Mail::assertNothingSent();
        })->with([
            'email only' => 'jane@example.com',
            'empty quotes' => 'jane@example.com ""',
        ]);

        it('rejects a message longer than 255 characters', function () {
            $html = contactCommandRun('jane@example.com '.str_repeat('a', 256));

            expect($html)->toContain('message too long — max 255 characters')
                ->and(ContactMessage::query()->count())->toBe(0);
            Mail::assertNothingSent();
        });
    });

    describe('daily limit', function () {
        beforeEach(function () {
            Mail::fake();
        });

        it('allows one message per email address per day', function () {
            contactCommandRun('jane@example.com First');
            Mail::fake();

            $html = contactCommandRun('jane@example.com Second');

            expect($html)->toContain("You've already left a message today")
                ->and(ContactMessage::query()->count())->toBe(1)
                ->and(ContactMessage::query()->sole()->message)->toBe('First');
            Mail::assertNothingSent();
        });

        it('treats the address case-insensitively', function () {
            contactCommandRun('jane@example.com First');

            expect(contactCommandRun('JANE@EXAMPLE.COM Second'))->toContain("You've already left a message today")
                ->and(ContactMessage::query()->count())->toBe(1);
        });

        it('lets a different address write on the same day', function () {
            contactCommandRun('jane@example.com First');
            contactCommandRun('john@example.com Second');

            expect(ContactMessage::query()->count())->toBe(2);
        });

        it('lets the same address write again on another day', function () {
            ContactMessage::factory()->sent()->create(['email' => 'jane@example.com', 'created_at' => now()->subDay()]);

            expect(contactCommandRun('jane@example.com Again'))->toContain('Got it')
                ->and(ContactMessage::query()->count())->toBe(2);
        });

        it('has no other throttling across different addresses', function () {
            foreach (range(1, 6) as $i) {
                contactCommandRun("visitor{$i}@example.com Hello");
            }

            expect(ContactMessage::query()->count())->toBe(6);
        });
    });

    describe('mail failures', function () {
        it('tells the visitor when the confirmation cannot be delivered', function () {
            $attempts = contactCommandBreakMail('Got your email');
            Log::spy();
            contactCommandOwner();

            $html = contactCommandRun('jane@example.com Hello');

            $message = ContactMessage::query()->sole();

            expect($html)->toContain('message delivery failed — please try again later')->not->toContain('Got it')
                ->and($message->visitor_status)->toBe(ContactMessageStatus::Failed)
                ->and($message->admin_status)->toBe(ContactMessageStatus::Pending)
                ->and($attempts->getArrayCopy())->toBe(['Got your email!']);
            Log::shouldHaveReceived('error')->once();
        });

        it('still confirms to the visitor when the owner notification fails', function () {
            $attempts = contactCommandBreakMail('New contact message');
            Log::spy();
            contactCommandOwner();

            $html = contactCommandRun('jane@example.com Hello');

            $message = ContactMessage::query()->sole();

            expect($html)->toContain('Got it')
                ->and($message->visitor_status)->toBe(ContactMessageStatus::Sent)
                ->and($message->admin_status)->toBe(ContactMessageStatus::Failed)
                ->and($attempts)->toHaveCount(2);
            Log::shouldHaveReceived('error')->once();
        });

        // Current behaviour: the failed row still counts as "a message today", so the visitor cannot retry.
        it('keeps blocking the address for the day after a failed delivery', function () {
            contactCommandBreakMail('Got your email');
            Log::spy();

            contactCommandRun('jane@example.com Hello');

            expect(contactCommandRun('jane@example.com Hello again'))->toContain("You've already left a message today")
                ->and(ContactMessage::query()->count())->toBe(1);
        });
    });
});
