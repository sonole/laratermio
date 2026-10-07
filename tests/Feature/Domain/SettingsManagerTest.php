<?php

use App\Enums\CvFont;
use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Facades\Settings;
use App\Models\Setting;
use App\Services\SettingsManager;
use Illuminate\Support\Facades\DB;

/** Write a setting row with a plain value, bypassing the CV-flavoured `cvSetting()` helper. */
function domainSetting(SettingKey $key, ?string $value, ?array $translations = null): Setting
{
    return Setting::query()->updateOrCreate(['key' => $key->value], [
        'group' => 'Test',
        'label' => $key->value,
        'type' => SettingType::String,
        'value' => $value,
        'translations' => $translations,
        'sort_order' => 0,
    ]);
}

describe('Settings facade', function () {
    it('resolves to the shared SettingsManager singleton', function () {
        expect(Settings::getFacadeRoot())->toBeInstanceOf(SettingsManager::class)
            ->and(Settings::getFacadeRoot())->toBe(app(SettingsManager::class));
    });

    it('reads a stored value', function () {
        domainSetting(SettingKey::SeoTitle, 'My Portfolio');

        expect(Settings::get(SettingKey::SeoTitle))->toBe('My Portfolio');
    });
});

describe('get', function () {
    it('returns null for a missing key without a default', function () {
        expect(Settings::get(SettingKey::SeoTwitterHandle))->toBeNull();
    });

    it('returns the default for a missing key', function () {
        expect(Settings::get(SettingKey::SeoTwitterHandle, '@nobody'))->toBe('@nobody');
    });

    it('returns the default when the row exists but its value is null', function () {
        domainSetting(SettingKey::SeoTwitterHandle, null);

        expect(Settings::get(SettingKey::SeoTwitterHandle, '@fallback'))->toBe('@fallback');
    });

    it('keeps an empty string instead of falling back to the default', function () {
        domainSetting(SettingKey::SeoTwitterHandle, '');

        expect(Settings::get(SettingKey::SeoTwitterHandle, '@fallback'))->toBe('');
    });
});

describe('per-request cache', function () {
    it('loads the whole settings table once, lazily', function () {
        domainSetting(SettingKey::SeoTitle, 'Title');
        domainSetting(SettingKey::SeoDescription, 'Description');

        DB::enableQueryLog();
        $manager = new SettingsManager;

        expect(DB::getQueryLog())->toBeEmpty('nothing is read before the first lookup');

        $manager->get(SettingKey::SeoTitle);
        $manager->get(SettingKey::SeoDescription);
        $manager->getBool(SettingKey::AsciiArtEnabled);
        $manager->get(SettingKey::Favicon);

        $settingQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], '"settings"'));

        expect($settingQueries)->toHaveCount(1);
    });

    it('does not see writes made after the first read (there is no invalidation)', function () {
        domainSetting(SettingKey::SeoTitle, 'Before');
        $manager = new SettingsManager;

        expect($manager->get(SettingKey::SeoTitle))->toBe('Before');

        Setting::query()->where('key', SettingKey::SeoTitle->value)->update(['value' => 'After']);

        expect($manager->get(SettingKey::SeoTitle))->toBe('Before')
            ->and((new SettingsManager)->get(SettingKey::SeoTitle))->toBe('After');
    });
});

describe('typed access', function () {
    it('reads booleans stored as "1" and "0"', function () {
        domainSetting(SettingKey::AsciiArtEnabled, '1');
        domainSetting(SettingKey::AsciiArt, '0');

        expect(Settings::getBool(SettingKey::AsciiArtEnabled))->toBeTrue()
            ->and(Settings::getBool(SettingKey::AsciiArt, true))->toBeFalse();
    });

    it('falls back to the boolean default when the setting is missing', function () {
        expect(Settings::getBool(SettingKey::AsciiArtEnabled))->toBeFalse()
            ->and(Settings::getBool(SettingKey::AsciiArtEnabled, true))->toBeTrue();
    });

    it('treats anything other than "1" as false', function () {
        domainSetting(SettingKey::AsciiArtEnabled, 'true');

        expect(Settings::getBool(SettingKey::AsciiArtEnabled, true))->toBeFalse();
    });

    it('reads numbers as floats', function () {
        domainSetting(SettingKey::AsciiArtSize, '0.70');

        expect(Settings::getFloat(SettingKey::AsciiArtSize))->toBe(0.7);
    });

    it('falls back to the float default for missing or non-numeric values', function () {
        expect(Settings::getFloat(SettingKey::AsciiArtSize))->toBeNull()
            ->and(Settings::getFloat(SettingKey::AsciiArtSize, 1.5))->toBe(1.5);

        domainSetting(SettingKey::AsciiArtSize, 'big');

        expect(Settings::getFloat(SettingKey::AsciiArtSize, 1.5))->toBe(1.5);
    });
});

describe('file and upload values', function () {
    it('uses the static favicon when none was uploaded', function () {
        expect(Settings::faviconUrl())->toBe('/favicon.ico');
    });

    it('resolves an uploaded favicon to its storage URL', function () {
        domainSetting(SettingKey::Favicon, 'uploads/settings/favicon.png');

        expect(Settings::faviconUrl())->toBe('/storage/uploads/settings/favicon.png');
    });

    it('keeps an absolute favicon path as it is', function () {
        domainSetting(SettingKey::Favicon, '/images/icon.svg');

        expect(Settings::faviconUrl())->toBe('/images/icon.svg');
    });

    it('has no share image URL until one is uploaded', function () {
        expect(Settings::ogImageUrl())->toBeNull();

        domainSetting(SettingKey::SeoOgImage, '');

        expect(Settings::ogImageUrl())->toBeNull();
    });

    it('builds an absolute share image URL from the app URL', function () {
        config(['app.url' => 'https://example.test/']);
        domainSetting(SettingKey::SeoOgImage, 'uploads/settings/og-image.png');

        expect(Settings::ogImageUrl())->toBe('https://example.test/storage/uploads/settings/og-image.png');
    });
});

describe('identity', function () {
    it('falls back to built-in defaults when nothing is stored', function () {
        expect(Settings::getName())->toBe('Dev McDevface')
            ->and(Settings::getRole())->toBe('Developer')
            ->and(Settings::getAbout())->toBe('');
    });

    it('returns the stored identity in the main language', function () {
        domainSetting(SettingKey::Name, 'Alex Example');
        domainSetting(SettingKey::Role, 'Engineer');
        domainSetting(SettingKey::About, 'Hello there');

        expect(Settings::getName())->toBe('Alex Example')
            ->and(Settings::getRole())->toBe('Engineer')
            ->and(Settings::getAbout())->toBe('Hello there')
            ->and(Settings::getName(config('app.locale')))->toBe('Alex Example');
    });

    it('returns the translation for another language and falls back to the main text', function () {
        domainSetting(SettingKey::Name, 'Alex Example', ['el' => 'Αλέξανδρος']);
        domainSetting(SettingKey::Role, 'Engineer');

        expect(Settings::getName('el'))->toBe('Αλέξανδρος')
            ->and(Settings::getRole('el'))->toBe('Engineer')
            ->and(Settings::getAbout('el'))->toBe('');
    });

    it('does not use blank translations', function () {
        domainSetting(SettingKey::Name, 'Alex Example', ['el' => '  ']);

        expect(Settings::getName('el'))->toBe('Alex Example');
    });
});

describe('getTranslated', function () {
    it('returns the main-language value for the base locale', function () {
        domainSetting(SettingKey::CvTitleSkills, 'Abilities');

        expect(Settings::getTranslated(SettingKey::CvTitleSkills, config('app.locale')))->toBe('Abilities');
    });

    it('returns null for the base locale when the value is blank or missing', function () {
        expect(Settings::getTranslated(SettingKey::CvTitleSkills, config('app.locale')))->toBeNull();

        domainSetting(SettingKey::CvTitleSkills, '');

        expect(Settings::getTranslated(SettingKey::CvTitleSkills, config('app.locale')))->toBeNull();
    });

    it('returns the stored translation for another locale, or null with no fallback', function () {
        domainSetting(SettingKey::CvTitleSkills, 'Abilities', ['el' => 'Ικανότητες']);

        expect(Settings::getTranslated(SettingKey::CvTitleSkills, 'el'))->toBe('Ικανότητες')
            ->and(Settings::getTranslated(SettingKey::CvTitleSkills, 'de'))->toBeNull()
            ->and(Settings::getTranslated(SettingKey::CvTitleProjects, 'el'))->toBeNull();
    });
});

describe('CV font', function () {
    it('defaults to the default font', function () {
        domainSetting(SettingKey::CvFont, null);

        expect(Settings::getCvFont())->toBe(CvFont::default());
    });

    it('returns the chosen font', function () {
        domainSetting(SettingKey::CvFont, 'times');

        expect(Settings::getCvFont())->toBe(CvFont::Times);
    });

    it('ignores an unknown font name', function () {
        domainSetting(SettingKey::CvFont, 'comic-sans');

        expect(Settings::getCvFont())->toBe(CvFont::default());
    });
});

describe('terminal prompt', function () {
    it('uses defaults when nothing is stored', function () {
        expect(Settings::getPromptUsername())->toBe('visitor')
            ->and(Settings::getPromptUsernameColor())->toBe('#4ade80')
            ->and(Settings::getPromptHostname())->toBe('localhost')
            ->and(Settings::getPromptHostnameColor())->toBe('#60a5fa')
            ->and(Settings::getPromptSeparatorColor())->toBe('#6b7280');
    });

    it('builds the working-directory suffix', function () {
        expect(Settings::getPromptSuffix())->toBe(':~$')
            ->and(Settings::getPromptSuffix('~/projects'))->toBe(':~/projects$');
    });

    it('renders a plain prompt', function () {
        domainSetting(SettingKey::PromptUsername, 'guest');
        domainSetting(SettingKey::PromptHostname, 'laratermio');

        expect(Settings::getPrompt(null, pretty: false))->toBe('guest@laratermio :~$ ')
            ->and(Settings::getPrompt('~/skills', pretty: false))->toBe('guest@laratermio :~/skills$ ');
    });

    it('renders a coloured jQuery.terminal prompt', function () {
        domainSetting(SettingKey::PromptUsername, 'guest');
        domainSetting(SettingKey::PromptUsernameColor, '#ff0000');
        domainSetting(SettingKey::PromptHostname, 'laratermio');
        domainSetting(SettingKey::PromptHostnameColor, '#00ff00');
        domainSetting(SettingKey::PromptSeparatorColor, '#0000ff');

        expect(Settings::getPrompt())->toBe('[[b;#ff0000;]guest][[;#0000ff;]@][[b;#00ff00;]laratermio][[;#0000ff;]:~$] ');
    });
});
