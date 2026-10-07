<?php

use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Setting;
use App\Models\SkillCategory;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create or update a setting. `$translations` is `[locale => text]` for translatable settings.
 *
 * @param  array<string, string>|null  $translations
 */
function cvSetting(SettingKey $key, ?string $value, ?array $translations = null): Setting
{
    return Setting::query()->updateOrCreate(['key' => $key->value], [
        'group' => 'CV',
        'label' => $key->value,
        'type' => SettingType::String,
        'value' => $value,
        'translations' => $translations,
        'sort_order' => 0,
    ]);
}

/** Switch on additional CV languages, as the admin would in Settings. */
function enableCvLocales(string ...$locales): void
{
    // Keep the real multiselect type, so the Settings page decodes the stored list like it does for real.
    cvSetting(SettingKey::CvLocales, json_encode($locales))->update(['type' => SettingType::MultiSelect]);
}

/** Sign in as an admin who has already changed their password, so Filament pages are reachable. */
function actingAsAdmin(): User
{
    $user = User::factory()->create(['must_change_password' => false]);

    test()->actingAs($user);

    return $user;
}

/** Register the terminal commands, nav items and settings the app ships with (no personal content). */
function seedSystem(): void
{
    test()->seed(SystemSeeder::class);
}

function seedEnglishCv(): void
{
    cvSetting(SettingKey::Name, 'Alex Example');
    cvSetting(SettingKey::Role, 'Software Engineer');
    cvSetting(SettingKey::About, 'Experienced developer focused on Laravel.');

    Experience::create(['title' => 'Senior Developer', 'company' => 'Acme Inc', 'start_date' => '2020-03-01', 'is_current' => true, 'bullets' => ['Built services']]);
}

function seedGreekCv(): void
{
    cvSetting(SettingKey::Name, 'Αλέξανδρος Παλιαμπέλος');
    cvSetting(SettingKey::Role, 'Μηχανικός Λογισμικού');
    cvSetting(SettingKey::About, 'Έμπειρος προγραμματιστής με εστίαση στο Laravel.');

    Experience::create(['title' => 'Ανώτερος Προγραμματιστής', 'company' => 'Εταιρεία Α.Ε.', 'start_date' => '2020-01-01', 'is_current' => true, 'bullets' => ['Σχεδίαση υπηρεσιών']]);
    Education::create(['title' => 'Πληροφορική', 'institution' => 'Πανεπιστήμιο Αθηνών', 'start_date' => '2012-09-01', 'end_date' => '2017-06-01']);
    SkillCategory::create(['name' => 'Γλώσσες', 'items' => ['PHP', 'JavaScript']]);
    ContactItem::create(['label' => 'me@example.com', 'icon' => 'fa-solid fa-envelope', 'url' => 'mailto:me@example.com']);
}

/** An English CV whose Greek translations are filled in. */
function seedTranslatedCv(): void
{
    seedEnglishCv();

    cvSetting(SettingKey::Name, 'Alex Example', ['el' => 'Αλέξανδρος Παράδειγμα']);
    cvSetting(SettingKey::Role, 'Software Engineer', ['el' => 'Μηχανικός Λογισμικού']);

    Experience::query()->update([
        'translations' => json_encode(['el' => ['title' => 'Ανώτερος Προγραμματιστής', 'bullets' => ['Σχεδίαση υπηρεσιών']]]),
    ]);
    Education::create(['title' => 'BSc Computer Science', 'institution' => 'University of Athens', 'start_date' => '2012-09-01', 'end_date' => '2017-06-01', 'translations' => ['el' => ['title' => 'Πτυχίο Πληροφορικής']]]);
    Education::create(['title' => 'AWS Certified', 'institution' => 'Amazon', 'start_date' => '2022-11-01', 'is_certification' => true]);
}
