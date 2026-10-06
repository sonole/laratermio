<?php

use App\Enums\CvSection;
use App\Enums\SettingKey;
use App\Facades\Settings;
use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillCategory;
use App\Services\CvLocales;

function experienceWithGreek(array $overrides = []): Experience
{
    return new Experience(array_merge([
        'title' => 'Senior Developer',
        'company' => 'Acme Inc',
        'start_date' => '2020-03-01',
        'bullets' => ['Built services', 'Mentored the team'],
        'translations' => [
            'el' => ['title' => 'Ανώτερος Προγραμματιστής', 'bullets' => ['Σχεδίαση υπηρεσιών']],
        ],
    ], $overrides));
}

describe('HasTranslations', function () {
    it('returns the stored translation for a language', function () {
        $experience = experienceWithGreek();

        expect($experience->translated('title', 'el'))->toBe('Ανώτερος Προγραμματιστής')
            ->and($experience->translated('bullets', 'el'))->toBe(['Σχεδίαση υπηρεσιών']);
    });

    it('falls back to the main language when a field has no translation', function () {
        $experience = experienceWithGreek();

        expect($experience->translated('company', 'el'))->toBe('Acme Inc')
            ->and($experience->translated('title', 'de'))->toBe('Senior Developer');
    });

    it('treats blank translations as missing', function (mixed $blank) {
        $experience = experienceWithGreek(['translations' => ['el' => ['title' => $blank, 'bullets' => $blank]]]);

        expect($experience->translated('title', 'el'))->toBe('Senior Developer')
            ->and($experience->translated('bullets', 'el'))->toBe(['Built services', 'Mentored the team']);
    })->with([
        'empty string' => [''],
        'whitespace' => ['   '],
        'null' => [null],
        'empty list' => [[]],
        'list of empty entries' => [['', '  ']],
    ]);

    it('drops empty entries from a translated list', function () {
        $experience = experienceWithGreek(['translations' => ['el' => ['bullets' => ['Πρώτο', '', 'Τρίτο']]]]);

        expect($experience->translated('bullets', 'el'))->toBe(['Πρώτο', 'Τρίτο']);
    });

    it('never lets stored text override the main language itself', function () {
        $experience = experienceWithGreek(['translations' => ['en' => ['title' => 'Should be ignored']]]);

        expect($experience->translated('title', 'en'))->toBe('Senior Developer');
    });

    it('builds an unsaved localized copy without the other languages in it', function () {
        $experience = experienceWithGreek();

        $copy = $experience->localized('el');

        expect($copy->title)->toBe('Ανώτερος Προγραμματιστής')
            ->and($copy->company)->toBe('Acme Inc')
            ->and($copy->bullets)->toBe(['Σχεδίαση υπηρεσιών'])
            ->and($copy->translations)->toBeNull()
            ->and($copy->exists)->toBeFalse();

        // the original record is untouched
        expect($experience->title)->toBe('Senior Developer')
            ->and($experience->translations)->not->toBeNull();
    });

    it('declares translatable fields that are real, fillable columns on every content model', function (string $model) {
        $instance = new $model;

        foreach ($model::translatableFields() as $field) {
            expect($instance->isFillable($field))->toBeTrue("$model::$field must be fillable");
        }

        expect($instance->isFillable('translations'))->toBeTrue();
    })->with([Experience::class, Education::class, Project::class, SkillCategory::class, ContactItem::class]);
});

describe('dates and wording', function () {
    it('keeps the existing English wording on the public site', function () {
        $experience = new Experience(['start_date' => '2020-03-01']);
        $current = new Education(['start_date' => '2012-09-01']);
        $past = new Education(['start_date' => '2012-09-01', 'end_date' => '2017-06-01']);
        $certificate = new Education(['start_date' => '2022-11-01', 'is_certification' => true]);

        expect($experience->period)->toBe('Mar 2020 – Present')
            ->and($current->period)->toBe('Sep 2012 – Present')
            ->and($past->period)->toBe('Sep 2012 – Jun 2017')
            ->and($certificate->period)->toBe('Issued on Nov 2022');
    });

    it('has complete wording for every language offered in config', function (string $locale) {
        foreach (CvSection::cases() as $section) {
            expect($section->defaultTitle($locale))->not->toBeEmpty()
                ->and(__('cv.sections.'.$section->value, [], $locale))->not->toBe('cv.sections.'.$section->value);
        }

        expect(__('cv.present', [], $locale))->not->toBe('cv.present')
            ->and(__('cv.issued_on', ['date' => 'X'], $locale))->toContain('X');
        // Datasets are built before the app boots, so `config()` is not available here.
    })->with(fn () => array_keys((require __DIR__.'/../../config/portfolio.php')['cv_locales']));
});

describe('settings in other languages', function () {
    it('falls back to the main-language value when a language has no text', function () {
        cvSetting(SettingKey::Name, 'Alex Example', ['el' => 'Αλέξανδρος']);
        cvSetting(SettingKey::Role, 'Engineer');

        expect(Settings::getName('el'))->toBe('Αλέξανδρος')
            ->and(Settings::getName('de'))->toBe('Alex Example')
            ->and(Settings::getName())->toBe('Alex Example')
            ->and(Settings::getRole('el'))->toBe('Engineer');
    });

    it('treats blank translated text as missing', function () {
        cvSetting(SettingKey::About, 'About me', ['el' => '   ']);

        expect(Settings::getAbout('el'))->toBe('About me');
    });

    it('uses built-in defaults when nothing is stored', function () {
        expect(Settings::getName('el'))->toBe('Dev McDevface')
            ->and(Settings::getRole('el'))->toBe('Developer')
            ->and(Settings::getAbout('el'))->toBe('');
    });

    it('reads the main-language override of a title from the setting value', function () {
        cvSetting(SettingKey::CvTitleSkills, 'Toolbox', ['el' => 'Εργαλειοθήκη']);

        expect(Settings::getTranslated(SettingKey::CvTitleSkills, 'en'))->toBe('Toolbox')
            ->and(Settings::getTranslated(SettingKey::CvTitleSkills, 'el'))->toBe('Εργαλειοθήκη')
            ->and(Settings::getTranslated(SettingKey::CvTitleSkills, 'de'))->toBeNull();
    });

    it('marks exactly the text settings as translatable', function () {
        expect(SettingKey::translatable())->toContain(SettingKey::Name, SettingKey::Role, SettingKey::About)
            ->and(SettingKey::PromptUsername->isTranslatable())->toBeFalse()
            ->and(SettingKey::CvFont->isTranslatable())->toBeFalse()
            ->and(SettingKey::CvTitleSkills->cvSection())->toBe(CvSection::Skills)
            ->and(SettingKey::Name->cvSection())->toBeNull();
    });
});

describe('CvLocales', function () {
    it('has no extra languages until some are switched on', function () {
        $locales = app(CvLocales::class);

        expect($locales->base())->toBe('en')
            ->and($locales->extra())->toBe([])
            ->and($locales->all())->toBe(['en']);
    });

    it('lists switched-on languages in config order', function () {
        enableCvLocales('de', 'el', 'it');

        expect(app(CvLocales::class)->extra())->toBe(['el', 'it', 'de'])
            ->and(app(CvLocales::class)->all())->toBe(['en', 'el', 'it', 'de']);
    });

    it('ignores unknown languages, the main language and duplicates', function () {
        enableCvLocales('el', 'xx', 'en', 'el');

        expect(app(CvLocales::class)->extra())->toBe(['el']);
    });

    it('survives garbage in the setting', function (string $garbage) {
        cvSetting(SettingKey::CvLocales, $garbage);

        expect(app(CvLocales::class)->extra())->toBe([]);
    })->with(['not json', '"el"', '42', 'null', '{"a":"el"}']);

    it('follows a different main language', function () {
        config(['app.locale' => 'el']);
        enableCvLocales('el', 'en');

        $locales = app(CvLocales::class);

        expect($locales->base())->toBe('el')
            ->and($locales->selectable())->not->toHaveKey('el')
            ->and($locales->extra())->toBe(['en'])
            ->and($locales->isEnabled('el'))->toBeTrue()
            ->and($locales->isBase('el'))->toBeTrue();
    });

    it('names languages natively and falls back to the code', function () {
        $locales = app(CvLocales::class);

        expect($locales->name('el'))->toBe('Ελληνικά')
            ->and($locales->name('xx'))->toBe('xx');
    });
});
