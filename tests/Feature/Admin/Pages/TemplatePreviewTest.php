<?php

use App\Enums\SettingKey;
use App\Filament\Pages\TemplatePreview;
use App\Services\TemplatePreviewService;
use Livewire\Livewire;

beforeEach(function () {
    actingAsAdmin();
    seedSystem();
    seedEnglishCv();
});

describe('template preview page', function () {
    it('renders for a signed-in admin', function () {
        $this->get('/admin/template-preview')->assertOk()->assertSee('Template');
    });

    it('offers every registered template', function () {
        expect(TemplatePreviewService::options())->toBe([
            'contact-message' => 'Contact Message',
            'contact-message-confirmation' => 'Contact Message Confirmation',
            'cv' => 'CV',
        ]);
    });

    it('selects and renders the first template when it opens', function () {
        $page = Livewire::test(TemplatePreview::class)
            ->assertSet('template', 'contact-message')
            ->assertFormSet(['template' => 'contact-message']);

        expect($page->get('previewHtml'))
            ->toContain('visitor@mail.com')
            ->toContain('This is a test message.');
    });

    it('renders each template with its own sample content', function (string $template, array $fragments) {
        $page = Livewire::test(TemplatePreview::class)->set('template', $template);

        expect($page->get('template'))->toBe($template);

        foreach ($fragments as $fragment) {
            expect($page->get('previewHtml'))->toContain($fragment);
        }

        expect($page->get('previewHtml'))->not->toContain('Error generating preview');
    })->with([
        'contact message' => ['contact-message', ['New contact message', 'visitor@mail.com', 'This is a test message.']],
        'confirmation' => ['contact-message-confirmation', ['your message', 'This is a test message.', 'I\'ll review it and get back to you']],
        'cv' => ['cv', ['Alex Example', 'SOFTWARE ENGINEER', 'Senior Developer', 'Acme Inc']],
    ]);

    it('replaces the preview when another template is picked', function () {
        $page = Livewire::test(TemplatePreview::class);
        $first = $page->get('previewHtml');

        $page->set('template', 'cv');

        expect($page->get('previewHtml'))->not->toBe($first)
            ->toContain('Alex Example')
            ->not->toContain('This is a test message.');
    });

    it('uses the current site settings in the mail previews', function () {
        cvSetting(SettingKey::PromptUsername, 'jdoe');

        $page = Livewire::test(TemplatePreview::class)->set('template', 'contact-message');

        expect($page->get('previewHtml'))->toContain('jdoe');
    });

    it('reflects the current CV content in the CV preview', function () {
        cvSetting(SettingKey::Name, 'Renamed Person');

        $page = Livewire::test(TemplatePreview::class)->set('template', 'cv');

        expect($page->get('previewHtml'))->toContain('Renamed Person');
    });

    it('says so when no template is selected', function () {
        $page = Livewire::test(TemplatePreview::class)->set('template', null);

        expect($page->get('previewHtml'))->toContain('Notice')->toContain('No template selected.');
    });

    it('shows an error instead of crashing for an unknown template', function () {
        $page = Livewire::test(TemplatePreview::class)->set('template', 'does-not-exist');

        expect($page->get('previewHtml'))
            ->toContain('Error generating preview')
            ->toContain('Unknown template preview [does-not-exist]');
    });

    it('escapes the error message', function () {
        $page = Livewire::test(TemplatePreview::class)->set('template', '<script>alert(1)</script>');

        expect($page->get('previewHtml'))
            ->not->toContain('<script>alert(1)</script>')
            ->toContain('&lt;script&gt;');
    });
});

describe('template preview service', function () {
    it('rejects unknown templates', function () {
        TemplatePreviewService::make('nope');
    })->throws(InvalidArgumentException::class, 'Unknown template preview [nope]');

    it('maps each template to an existing view', function (string $key) {
        expect(view()->exists(TemplatePreviewService::view($key)))->toBeTrue()
            ->and(TemplatePreviewService::data($key))->toBeArray()->not->toBeEmpty();
    })->with(['contact-message', 'contact-message-confirmation', 'cv']);
});
