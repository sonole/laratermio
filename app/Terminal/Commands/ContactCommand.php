<?php

namespace App\Terminal\Commands;

use App\Enums\ContactMessageStatus;
use App\Facades\Settings;
use App\Mail\ContactMessageConfirmationMail;
use App\Mail\ContactMessageMail;
use App\Models\ContactItem;
use App\Models\ContactMessage;
use App\Models\User;
use App\Terminal\TerminalResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactCommand extends BaseCommand
{
    public function name(): string
    {
        return 'contact';
    }

    protected function execute(?string $arg): TerminalResponse
    {
        return $arg !== null
            ? $this->executeNotification($arg)
            : $this->executeContactItems();
    }

    public function helpOptions(): array
    {
        return [
            ['option' => 'contact <email> <message>', 'description' => 'Drop your email and a short message'],
        ];
    }

    protected function executeContactItems(): TerminalResponse
    {
        $items = ContactItem::activeOrdered()->get();

        if ($items->isEmpty()) {
            return TerminalResponse::echo($this->renderError('no contact entries found.'));
        }

        $name = e(Settings::getName());
        $role = e(Settings::getRole());

        $rows = $items->map(function (ContactItem $item) {
            $raw = $item->icon ?? '·';
            $icon = str_starts_with($raw, 'fa')
                ? '<i class="'.e($raw).' fa-fw"></i>'
                : e($raw);
            $label = $item->url
                ? '<a class="t-link" href="'.e($item->url).'" target="_blank">'.e($item->label).'</a>'
                : '<span>'.e($item->label).'</span>';

            return <<<HTML
            <div class="t-contact-row">
                <span class="t-contact-icon">$icon</span>
                {$label}
            </div>
            HTML;
        })->implode('');

        return TerminalResponse::echo(<<<HTML
        <div class="t-block">
            {$this->header('contact')}
            <div class="t-contact-card">
                <div class="t-contact-top">
                    <span class="t-accent t-contact-name">$name</span>
                    <span class="t-dim">$role</span>
                </div>
                <div class="t-contact-divider"></div>
                <div class="t-contact-rows">$rows</div>
            </div>
        </div>
        HTML);
    }

    protected function executeNotification(string $arg): TerminalResponse
    {
        $parts = preg_split('/\s+/', $arg, 2) ?: [$arg];
        $email = strtolower(trim($parts[0]));
        $message = isset($parts[1]) ? trim($parts[1], " \"'") : null;

        $validator = Validator::make(
            ['email' => $email, 'message' => $message],
            ['email' => ['required', 'email'], 'message' => ['required', 'string', 'max:255']],
        );

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->has('email')) {
                return TerminalResponse::echo($this->renderError('invalid email address: <strong>'.e($email).'</strong>'));
            }

            if ($errors->has('message') && empty($message)) {
                return TerminalResponse::echo($this->renderError('usage: contact &lt;email&gt; &quot;&lt;message&gt;&quot;'));
            }

            return TerminalResponse::echo($this->renderError('message too long — max 255 characters'));
        }

        if (ContactMessage::query()->where('email', $email)->whereDate('created_at', today())->exists()) {
            return TerminalResponse::echo(<<<HTML
            <div class="t-block">
                {$this->header('contact')}
                <p class="t-dim">You've already left a message today — I'll be in touch soon.</p>
            </div>
            HTML);
        }

        $contactMessage = ContactMessage::query()->create([
            'email' => $email,
            'message' => $message,
        ]);

        try {
            Mail::to($email)->send(new ContactMessageConfirmationMail($contactMessage));
            $contactMessage->update(['visitor_status' => ContactMessageStatus::Sent]);
        } catch (\Throwable $e) {
            Log::error('contact command: visitor confirmation failed', ['error' => $e->getMessage(), 'email' => $email]);
            $contactMessage->update(['visitor_status' => ContactMessageStatus::Failed]);

            return TerminalResponse::echo($this->renderError('message delivery failed — please try again later'));
        }

        // fail silently
        try {
            $owner = User::query()->firstWhere('email', config('app.admin.email'));
            if ($owner) {
                Mail::to($owner->email)->send(new ContactMessageMail($contactMessage));
            }
            $contactMessage->update(['admin_status' => ContactMessageStatus::Sent]);
        } catch (\Throwable $e) {
            Log::error('contact command: admin notification failed', ['error' => $e->getMessage(), 'email' => $email]);
            $contactMessage->update(['admin_status' => ContactMessageStatus::Failed]);
        }

        $safe = e($email);

        return TerminalResponse::echo(<<<HTML
        <div class="t-block">
            {$this->header('contact')}
            <p><span class="t-accent">✓</span> Got it — <span class="t-label">$safe</span></p>
            <p class="t-dim t-mt">I'll reach out soon.</p>
        </div>
        HTML);
    }
}
