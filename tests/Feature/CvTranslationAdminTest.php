<?php

use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\ContactItems\Pages\EditContactItem;
use App\Filament\Resources\Educations\Pages\EditEducation;
use App\Filament\Resources\Experiences\Pages\EditExperience;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\SkillCategories\Pages\EditSkillCategory;
use App\Filament\Widgets\CvWidget;
use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SkillCategory;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['must_change_password' => false]));
});

/** The `settings` rows the migration creates should exist; give them realistic groups/labels. */
function settingValue(SettingKey $key): ?string
{
    return Setting::query()->where('key', $key->value)->value('value');
}

describe('Settings page', function () {
    it('ships the CV language and title settings through the migration', function () {
        $rows = Setting::query()->whereIn('key', [
            SettingKey::CvLocales->value,
            SettingKey::CvTitleObjective->value,
            SettingKey::CvTitleExperience->value,
            SettingKey::CvTitleEducation->value,
            SettingKey::CvTitleSkills->value,
            SettingKey::CvTitleProjects->value,
        ])->get()->keyBy('key');

        expect($rows)->toHaveCount(6)
            ->and($rows[SettingKey::CvLocales->value]->type)->toBe(SettingType::MultiSelect)
            ->and($rows[SettingKey::CvTitleSkills->value]->group)->toBe('CV');
    });

    it('lets the admin choose additional CV languages', function () {
        Livewire::test(ManageSettings::class)
            ->fillForm(['cv_locales' => ['el', 'de']])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(json_decode(settingValue(SettingKey::CvLocales), true))->toBe(['el', 'de']);
    });

    it('does not offer the main language as an additional language', function () {
        Livewire::test(ManageSettings::class)
            ->fillForm(['cv_locales' => ['en']])
            ->call('save')
            ->assertHasFormErrors(['cv_locales.0']);
    });

    it('shows no translation inputs until a language is switched on', function () {
        Livewire::test(ManageSettings::class)
            ->assertFormFieldDoesNotExist('translations.el.cv_title_experience');
    });

    it('keeps the CV section titles in the default tab of the CV translations, even with no extra language', function () {
        cvSetting(SettingKey::CvTitleExperience, 'Career');

        Livewire::test(ManageSettings::class)
            ->assertFormFieldExists('cv_title_objective')
            ->assertFormFieldExists('cv_title_experience')
            ->assertFormFieldExists('cv_title_education')
            ->assertFormFieldExists('cv_title_skills')
            ->assertFormFieldExists('cv_title_projects')
            ->assertFormSet(['cv_title_experience' => 'Career'])
            ->fillForm(['cv_title_skills' => 'Toolbox'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(settingValue(SettingKey::CvTitleSkills))->toBe('Toolbox');
    });

    it('shows name, role and about only in the translation tabs, next to the titles', function () {
        cvSetting(SettingKey::Name, 'Alex');
        cvSetting(SettingKey::Role, 'Engineer');
        cvSetting(SettingKey::About, 'About me');

        Livewire::test(ManageSettings::class)
            ->assertFormFieldExists('cv_title_objective')
            ->assertFormFieldDoesNotExist('translations.el.name')
            ->set('data.cv_locales', ['el'])
            ->assertFormFieldIsVisible('translations.el.name')
            ->assertFormFieldIsVisible('translations.el.role')
            ->assertFormFieldIsVisible('translations.el.about')
            ->assertFormFieldIsVisible('translations.el.cv_title_projects');
    });

    it('adds and removes translation inputs as languages are picked, without saving or reloading', function () {
        Livewire::test(ManageSettings::class)
            ->assertFormFieldDoesNotExist('translations.el.cv_title_experience')
            ->set('data.cv_locales', ['el'])
            ->assertFormFieldIsVisible('translations.el.cv_title_experience')
            ->assertFormFieldDoesNotExist('translations.de.cv_title_experience')
            ->set('data.cv_locales', ['el', 'de'])
            ->assertFormFieldIsVisible('translations.de.cv_title_experience')
            ->set('data.cv_locales', [])
            ->assertFormFieldDoesNotExist('translations.el.cv_title_experience');
    });

    it('shows the inputs right after saving a newly chosen language', function () {
        Livewire::test(ManageSettings::class)
            ->set('data.cv_locales', ['el'])
            ->call('save')
            ->assertFormFieldIsVisible('translations.el.cv_title_experience');
    });

    it('shows text stored earlier when a language is switched back on', function () {
        cvSetting(SettingKey::Name, 'Alex', ['el' => 'Αλέξ']);

        Livewire::test(ManageSettings::class)
            ->set('data.cv_locales', ['el'])
            ->assertFormSet(['translations.el.name' => 'Αλέξ'])
            ->call('save');

        expect(Setting::query()->where('key', 'name')->first()->translation('el'))->toBe('Αλέξ');
    });

    it('keeps the stored text of a language removed in the form', function () {
        enableCvLocales('el');
        cvSetting(SettingKey::Name, 'Alex', ['el' => 'Αλέξ']);

        Livewire::test(ManageSettings::class)
            ->set('data.cv_locales', [])
            ->call('save');

        expect(Setting::query()->where('key', 'name')->first()->translation('el'))->toBe('Αλέξ');
    });

    it('saves translated name, role, about and section titles per language', function () {
        cvSetting(SettingKey::Name, 'Alex');
        cvSetting(SettingKey::Role, 'Engineer');
        cvSetting(SettingKey::About, 'About me');
        enableCvLocales('el', 'de');

        Livewire::test(ManageSettings::class)
            ->assertFormFieldIsVisible('translations.el.name')
            ->assertFormFieldIsVisible('translations.de.cv_title_experience')
            ->fillForm(['translations' => [
                'el' => ['name' => 'Αλέξανδρος', 'about' => 'Σχετικά με εμένα', 'cv_title_experience' => 'Πορεία'],
                'de' => ['role' => 'Entwickler'],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $name = Setting::query()->where('key', 'name')->first();
        $title = Setting::query()->where('key', 'cv_title_experience')->first();

        expect($name->translation('el'))->toBe('Αλέξανδρος')
            ->and($name->translation('de'))->toBeNull()
            ->and(Setting::query()->where('key', 'role')->first()->translation('de'))->toBe('Entwickler')
            ->and($title->translation('el'))->toBe('Πορεία')
            // the main-language value is untouched by translation inputs
            ->and($name->value)->not->toBe('Αλέξανδρος');
    });

    it('removes a translation when its input is emptied', function () {
        enableCvLocales('el');
        cvSetting(SettingKey::Name, 'Alex', ['el' => 'Αλέξ', 'de' => 'Alexander']);

        Livewire::test(ManageSettings::class)
            ->assertFormSet(['translations.el.name' => 'Αλέξ'])
            ->fillForm(['translations' => ['el' => ['name' => '']]])
            ->call('save');

        $name = Setting::query()->where('key', 'name')->first();

        expect($name->translation('el'))->toBeNull()
            // 'de' is switched off, so it was not on the form and must be kept
            ->and($name->translation('de'))->toBe('Alexander');
    });

    it('still saves ordinary settings without touching translations when no language is on', function () {
        cvSetting(SettingKey::Name, 'Alex', ['el' => 'Αλέξ']);

        Livewire::test(ManageSettings::class)
            ->fillForm(['name' => 'Alexandros'])
            ->call('save')
            ->assertHasNoFormErrors();

        $name = Setting::query()->where('key', 'name')->first();

        expect($name->value)->toBe('Alexandros')
            ->and($name->translation('el'))->toBe('Αλέξ');
    });
});

describe('content forms', function () {
    it('hides the translation inputs when no language is switched on', function () {
        $experience = Experience::create(['title' => 'Dev', 'company' => 'Acme', 'start_date' => '2020-01-01']);

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertFormFieldDoesNotExist('translations.el.title');
    });

    it('saves experience translations', function () {
        enableCvLocales('el');
        $experience = Experience::create(['title' => 'Dev', 'company' => 'Acme', 'start_date' => '2020-01-01', 'bullets' => ['Built']]);

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertFormFieldExists('translations.el.title')
            ->assertFormFieldExists('translations.el.company')
            ->assertFormFieldExists('translations.el.bullets')
            ->fillForm(['translations' => ['el' => ['title' => 'Προγραμματιστής', 'bullets' => [['value' => 'Έχτισα']]]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $experience->refresh();

        expect($experience->title)->toBe('Dev')
            ->and($experience->translated('title', 'el'))->toBe('Προγραμματιστής')
            ->and($experience->translated('bullets', 'el'))->toBe(['Έχτισα'])
            ->and($experience->translated('company', 'el'))->toBe('Acme');
    });

    it('keeps stored translations when the form has no translation inputs', function () {
        $experience = Experience::create([
            'title' => 'Dev', 'company' => 'Acme', 'start_date' => '2020-01-01',
            'translations' => ['el' => ['title' => 'Προγραμματιστής']],
        ]);

        // no language switched on, so the inputs are not on the form
        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm(['title' => 'Developer'])
            ->call('save');

        expect($experience->refresh()->title)->toBe('Developer')
            ->and($experience->translation('title', 'el'))->toBe('Προγραμματιστής');
    });

    it('loads stored list translations into the form and saves them back unchanged', function () {
        enableCvLocales('el');
        $experience = Experience::create([
            'title' => 'Dev', 'company' => 'Acme', 'start_date' => '2020-01-01', 'bullets' => ['Built'],
            'translations' => ['el' => ['title' => 'Προγραμματιστής', 'bullets' => ['Πρώτο', 'Δεύτερο']]],
        ]);

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertFormSet(['translations.el.title' => 'Προγραμματιστής'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($experience->refresh()->translated('bullets', 'el'))->toBe(['Πρώτο', 'Δεύτερο'])
            ->and($experience->bullets)->toBe(['Built']);
    });

    it('saves education, project, skill category and contact translations', function () {
        enableCvLocales('el');

        $education = Education::create(['title' => 'BSc', 'institution' => 'Uni', 'start_date' => '2012-09-01']);
        Livewire::test(EditEducation::class, ['record' => $education->getKey()])
            ->fillForm(['translations' => ['el' => ['title' => 'Πτυχίο', 'description' => 'Πληροφορική']]])
            ->call('save')
            ->assertHasNoFormErrors();
        expect($education->refresh()->translation('title', 'el'))->toBe('Πτυχίο')
            ->and($education->translation('description', 'el'))->toBe('Πληροφορική');

        $project = Project::create(['name' => 'Tool', 'bullets' => ['Fast']]);
        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['translations' => ['el' => ['name' => 'Εργαλείο', 'subtitle' => 'Γρήγορο', 'bullets' => [['value' => 'Γρήγορο']]]]])
            ->call('save')
            ->assertHasNoFormErrors();
        expect($project->refresh()->translation('name', 'el'))->toBe('Εργαλείο')
            ->and($project->translation('bullets', 'el'))->toBe(['Γρήγορο']);

        $category = SkillCategory::create(['name' => 'Languages', 'items' => ['PHP']]);
        Livewire::test(EditSkillCategory::class, ['record' => $category->getKey()])
            ->fillForm(['translations' => ['el' => ['name' => 'Γλώσσες', 'items' => [['value' => 'PHP'], ['value' => 'Ελληνικά']]]]])
            ->call('save')
            ->assertHasNoFormErrors();
        expect($category->refresh()->translation('name', 'el'))->toBe('Γλώσσες')
            ->and($category->translation('items', 'el'))->toBe(['PHP', 'Ελληνικά']);

        $contact = ContactItem::create(['label' => 'Athens', 'icon' => 'fa-solid fa-location-dot']);
        Livewire::test(EditContactItem::class, ['record' => $contact->getKey()])
            ->fillForm(['translations' => ['el' => ['label' => 'Αθήνα']]])
            ->call('save')
            ->assertHasNoFormErrors();
        expect($contact->refresh()->translation('label', 'el'))->toBe('Αθήνα');
    });
});

describe('CV widget', function () {
    beforeEach(function () {
        seedTranslatedCv();
        enableCvLocales('el');
    });

    it('lists the extra languages', function () {
        $widget = Livewire::test(CvWidget::class);

        expect($widget->instance()->extraLanguages())->toBe(['el' => 'Ελληνικά']);
    });

    it('downloads the CV in an extra language without storing it', function () {
        Storage::fake('public');

        Livewire::test(CvWidget::class)
            ->call('download', 'el')
            ->assertFileDownloaded('aleksandros-paradeighma_cv_el.pdf');

        expect(Storage::disk('public')->allFiles())->toBe([]);
    });

    it('refuses languages that are not switched on, and the main language', function (string $locale) {
        Livewire::test(CvWidget::class)
            ->call('download', $locale)
            ->assertNotFound();
    })->with(['not enabled' => 'fr', 'main language' => 'en', 'nonsense' => '../../etc/passwd']);

    it('still generates the public main-language CV', function () {
        Storage::fake('public');

        Livewire::test(CvWidget::class)->call('generate');

        Storage::disk('public')->assertExists('uploads/cv/cv.pdf');
    });
});
