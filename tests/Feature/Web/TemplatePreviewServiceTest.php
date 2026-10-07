<?php

use App\Contracts\TemplatePreview;
use App\Enums\SettingKey;
use App\Models\ContactMessage;
use App\Services\TemplatePreviews\ContactMessageConfirmationPreview;
use App\Services\TemplatePreviews\ContactMessagePreview;
use App\Services\TemplatePreviews\CvPreview;
use App\Services\TemplatePreviewService;

describe('registry', function () {
    it('registers the three previewable templates', function () {
        expect(TemplatePreviewService::templates())->toBe([
            'contact-message' => ContactMessagePreview::class,
            'contact-message-confirmation' => ContactMessageConfirmationPreview::class,
            'cv' => CvPreview::class,
        ]);
    });

    it('registers every preview class that ships in the TemplatePreviews folder', function () {
        $shipped = collect(glob(app_path('Services/TemplatePreviews/*.php')))
            ->map(fn (string $file) => 'App\\Services\\TemplatePreviews\\'.basename($file, '.php'))
            ->sort()
            ->values()
            ->all();

        $registered = collect(TemplatePreviewService::templates())->values()->sort()->values()->all();

        expect($registered)->toBe($shipped);
    });

    it('lists a human readable label for each template', function () {
        expect(TemplatePreviewService::options())->toBe([
            'contact-message' => 'Contact Message',
            'contact-message-confirmation' => 'Contact Message Confirmation',
            'cv' => 'CV',
        ]);
    });
});

describe('make', function () {
    it('builds the preview for a key', function (string $key, string $class) {
        $preview = TemplatePreviewService::make($key);

        expect($preview)->toBeInstanceOf(TemplatePreview::class)
            ->toBeInstanceOf($class);
    })->with([
        'contact message' => ['contact-message', ContactMessagePreview::class],
        'confirmation' => ['contact-message-confirmation', ContactMessageConfirmationPreview::class],
        'cv' => ['cv', CvPreview::class],
    ]);

    it('rejects an unknown key', function () {
        TemplatePreviewService::make('nope');
    })->throws(InvalidArgumentException::class, 'Unknown template preview [nope]');

    it('rejects an unknown key from view() and data() too', function (string $method) {
        expect(fn () => TemplatePreviewService::$method('nope'))
            ->toThrow(InvalidArgumentException::class, 'Unknown template preview [nope]');
    })->with(['view', 'data']);
});

describe('views and data', function () {
    it('names the view each template renders', function (string $key, string $view) {
        expect(TemplatePreviewService::view($key))->toBe($view)
            ->and(view()->exists($view))->toBeTrue();
    })->with([
        'contact message' => ['contact-message', 'mail.contact-message'],
        'confirmation' => ['contact-message-confirmation', 'mail.contact-message-confirmation'],
        'cv' => ['cv', 'cv'],
    ]);

    it('previews a sample visitor message for the contact mails', function (string $key) {
        $data = TemplatePreviewService::data($key);

        expect($data['contactMessage'])->toBeInstanceOf(ContactMessage::class)
            ->and($data['contactMessage']->email)->toBe('visitor@mail.com')
            ->and($data['contactMessage']->message)->toBe('This is a test message.')
            ->and($data['contactMessage']->exists)->toBeFalse();
    })->with(['contact-message', 'contact-message-confirmation']);

    it('styles the contact mail previews with the terminal prompt settings', function () {
        cvSetting(SettingKey::PromptUsername, 'alex');
        cvSetting(SettingKey::PromptHostname, 'folio.dev');
        cvSetting(SettingKey::PromptUsernameColor, '#111111');

        $data = TemplatePreviewService::data('contact-message');

        expect($data)->toMatchArray([
            'username' => 'alex',
            'hostname' => 'folio.dev',
            'usernameColor' => '#111111',
            'hostnameDisplay' => 'folio&zwnj;.dev',
        ]);
    });

    it('greets the visitor by the configured name in the confirmation preview', function () {
        cvSetting(SettingKey::Name, 'Alex Example');

        expect(TemplatePreviewService::data('contact-message-confirmation'))->toHaveKey('name', 'Alex Example');
    });

    it('previews the CV with the live CV content', function () {
        seedEnglishCv();

        $data = TemplatePreviewService::data('cv');

        expect($data)->toMatchArray(['name' => 'Alex Example', 'role' => 'Software Engineer', 'forPdf' => false])
            ->and($data['experiences'])->toHaveCount(1);
    });

    it('renders every template with its own preview data', function (string $key) {
        seedEnglishCv();

        $html = view(TemplatePreviewService::view($key), TemplatePreviewService::data($key))->render();

        expect($html)->toContain('<html');
    })->with(['contact-message', 'contact-message-confirmation', 'cv']);

    it('shows the sample message in the rendered contact mail', function () {
        $html = view(TemplatePreviewService::view('contact-message'), TemplatePreviewService::data('contact-message'))->render();

        expect($html)->toContain('visitor@mail.com')
            ->toContain('This is a test message.');
    });
});

describe('TemplatePreview contract', function () {
    it('is implemented by every registered preview with a label, view and data', function (string $class) {
        $preview = app($class);

        expect($preview)->toBeInstanceOf(TemplatePreview::class)
            ->and($preview->templatePreviewLabel())->toBeString()->not->toBeEmpty()
            ->and($preview->templatePreviewView())->toBeString()->not->toBeEmpty()
            ->and($preview->templatePreviewData())->toBeArray()->not->toBeEmpty();
    })->with(fn () => array_values(TemplatePreviewService::templates()));
});
