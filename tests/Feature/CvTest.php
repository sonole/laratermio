<?php

use App\Enums\SettingKey;
use App\Services\CvService;
use Illuminate\Support\Facades\Storage;

describe('CV rendering', function () {
    it('renders Greek content with an embedded Unicode font instead of question marks', function () {
        seedGreekCv();

        $pdf = app(CvService::class)->render();

        expect($pdf)->toStartWith('%PDF')
            ->toContain('DejaVuSans')
            ->not->toContain('/BaseFont /Helvetica');
    });

    it('uppercases Greek headings with Greek rules', function () {
        seedGreekCv();

        $html = view('cv', CvService::getVariables(forPdf: true))->render();

        expect($html)->toContain('ΜΗΧΑΝΙΚΟΣ ΛΟΓΙΣΜΙΚΟΥ')
            ->not->toContain('ΜΗΧΑΝΙΚΌΣ');
    });

    it('keeps the original look for English-only content', function () {
        seedEnglishCv();

        expect(app(CvService::class)->render())
            ->toContain('/BaseFont /Helvetica')
            ->not->toContain('DejaVu');
    });

    it('honours the CV font setting', function (string $font, string $expectedBaseFont) {
        seedEnglishCv();
        cvSetting(SettingKey::CvFont, $font);

        expect(app(CvService::class)->render())->toContain($expectedBaseFont);
    })->with([
        'times' => ['times', '/BaseFont /Times-Roman'],
        'courier' => ['courier', '/BaseFont /Courier'],
        'dejavu sans' => ['dejavu-sans', 'DejaVuSans'],
        'dejavu serif' => ['dejavu-serif', 'DejaVuSerif'],
        'dejavu mono' => ['dejavu-sans-mono', 'DejaVuSansMono'],
    ]);

    it('swaps a Latin-only font for its DejaVu equivalent when content is Greek', function () {
        seedGreekCv();
        cvSetting(SettingKey::CvFont, 'times');

        expect(app(CvService::class)->render())
            ->toContain('DejaVuSerif')
            ->not->toContain('/BaseFont /Times');
    });

    it('falls back to the default font when the setting holds an unknown value', function () {
        seedEnglishCv();
        cvSetting(SettingKey::CvFont, 'comic-sans');

        expect(app(CvService::class)->render())->toContain('/BaseFont /Helvetica');
    });

    it('ships the icon fonts the template loads', function () {
        expect(public_path('fonts/fa/fa-solid-900.ttf'))->toBeFile()
            ->and(public_path('fonts/fa/fa-brands-400.ttf'))->toBeFile();
    });

    it('does not subset fonts, which corrupts the bundled Font Awesome icons', function () {
        seedGreekCv();

        // A subsetted font is named with a tag prefix, e.g. `/BaseFont /SUBAAD+FontAwesome7Free-Solid`.
        expect(app(CvService::class)->render())->not->toMatch('/\/BaseFont \/[A-Z]{6}\+/');
    });
});

describe('CV in other languages', function () {
    beforeEach(function () {
        seedTranslatedCv();
        enableCvLocales('el');
    });

    it('translates the content, section titles, dates and uppercases the Greek way', function () {
        $html = app(CvService::class)->html('el');

        expect($html)
            ->toContain('<html lang="el">')
            ->toContain('Αλέξανδρος Παράδειγμα')
            ->toContain('ΜΗΧΑΝΙΚΟΣ ΛΟΓΙΣΜΙΚΟΥ')
            ->toContain('ΕΠΑΓΓΕΛΜΑΤΙΚΟΣ ΣΤΟΧΟΣ')
            ->toContain('ΕΜΠΕΙΡΙΑ')
            ->toContain('ΕΚΠΑΙΔΕΥΣΗ')
            ->toContain('Ανώτερος Προγραμματιστής')
            ->toContain('Μαρ 2020 – Σήμερα')
            ->toContain('Εκδόθηκε: Νοε 2022')
            ->not->toContain('Present');
    });

    it('falls back to the main language for anything not translated', function () {
        $html = app(CvService::class)->html('el');

        expect($html)->toContain('Acme Inc')           // company has no Greek text
            ->toContain('AWS Certified')               // whole record untranslated
            ->toContain('Πτυχίο Πληροφορικής')         // translated title
            ->toContain('University of Athens');       // institution untranslated
    });

    it('does not leak other languages into the main-language CV', function () {
        $html = app(CvService::class)->html();

        expect($html)->toContain('<html lang="en">')
            ->toContain('Mar 2020 – Present')
            ->toContain('SOFTWARE ENGINEER')
            ->not->toContain('Μηχανικός')
            ->not->toContain('Ανώτερος');
    });

    it('uses an admin override for a section title in that language only', function () {
        cvSetting(SettingKey::CvTitleExperience, 'Work History', ['el' => 'Επαγγελματική Πορεία']);

        expect(app(CvService::class)->html('el'))->toContain('ΕΠΑΓΓΕΛΜΑΤΙΚΗ ΠΟΡΕΙΑ')
            ->and(app(CvService::class)->html())->toContain('WORK HISTORY');
    });

    it('does not reuse the main-language title override in a language without one', function () {
        cvSetting(SettingKey::CvTitleExperience, 'Work History'); // no Greek override

        $html = app(CvService::class)->html('el');

        expect($html)->toContain('ΕΜΠΕΙΡΙΑ')->not->toContain('WORK HISTORY');
    });

    it('picks the font from the language being rendered, not from other languages', function () {
        $service = app(CvService::class);

        expect($service->render())->toContain('/BaseFont /Helvetica')->not->toContain('DejaVu')
            ->and($service->render('el'))->toContain('DejaVuSans');
    });

    it('leaves the main language and translator untouched after rendering', function () {
        app(CvService::class)->render('el');

        expect(config('app.locale'))->toBe('en')
            ->and(app('translator')->getLocale())->toBe('en')
            ->and(__('cv.present'))->toBe('Present');
    });

    it('refuses a language that is not switched on', function () {
        app(CvService::class)->render('fr');
    })->throws(InvalidArgumentException::class, 'CV language [fr] is not enabled.');

    it('names the downloaded file after the language', function () {
        $service = app(CvService::class);

        expect($service->filename())->toBe('alex-example_cv.pdf')
            ->and($service->filename('el'))->toBe('aleksandros-paradeighma_cv_el.pdf');
    });
});

describe('CV storage and download', function () {
    beforeEach(fn () => Storage::fake(CvService::DISK));

    it('stores the generated PDF on the public disk', function () {
        seedGreekCv();

        $service = app(CvService::class);
        expect($service->exists())->toBeFalse();

        $service->generate();

        expect($service->exists())->toBeTrue();
        Storage::disk(CvService::DISK)->assertExists(CvService::PATH);
    });

    it('returns 404 until a CV has been generated', function () {
        $this->get(route('cv'))->assertNotFound();
    });

    it('serves the generated PDF inline', function () {
        seedGreekCv();
        app(CvService::class)->generate();

        $this->get(route('cv'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    });

    it('only ever stores and serves the main-language CV', function () {
        seedTranslatedCv();
        enableCvLocales('el', 'de');

        app(CvService::class)->generate();

        expect(Storage::disk(CvService::DISK)->allFiles('uploads/cv'))->toBe([CvService::PATH]);

        foreach (['el', 'de', 'en'] as $locale) {
            $this->get("/cv/$locale")->assertNotFound();
        }
    });
});
