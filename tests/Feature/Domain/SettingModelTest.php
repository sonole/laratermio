<?php

use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Models\Setting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

function domainMakeSetting(array $attributes = []): Setting
{
    // Some keys are already created by the migrations, so update instead of insert.
    $attributes += ['key' => 'test_'.uniqid()];

    return Setting::query()->updateOrCreate(['key' => $attributes['key']], $attributes + [
        'group' => 'Test',
        'label' => 'Test setting',
        'type' => SettingType::String,
        'value' => null,
        'sort_order' => 0,
    ]);
}

describe('casts', function () {
    it('casts the type column to the SettingType enum', function () {
        $setting = domainMakeSetting(['type' => 'color'])->fresh();

        expect($setting->type)->toBe(SettingType::Color);
    });

    it('casts translations to an array and keeps them null when unset', function () {
        $with = domainMakeSetting(['translations' => ['el' => 'Γειά']])->fresh();
        $without = domainMakeSetting()->fresh();

        expect($with->translations)->toBe(['el' => 'Γειά'])
            ->and($without->translations)->toBeNull();
    });

    it('rejects mass assignment of unknown attributes', function () {
        $setting = new Setting(['key' => 'x', 'bogus' => 'nope']);

        expect($setting->key)->toBe('x')
            ->and($setting->getAttributes())->not->toHaveKey('bogus');
    });
});

describe('translation()', function () {
    it('returns the text for a locale', function () {
        $setting = domainMakeSetting(['translations' => ['el' => 'Ελληνικά', 'de' => 'Deutsch']]);

        expect($setting->translation('el'))->toBe('Ελληνικά')
            ->and($setting->translation('de'))->toBe('Deutsch');
    });

    it('returns null for a locale without text', function () {
        $setting = domainMakeSetting(['translations' => ['el' => 'Ελληνικά']]);

        expect($setting->translation('fr'))->toBeNull();
    });

    it('returns null when there are no translations at all', function () {
        expect(domainMakeSetting()->translation('el'))->toBeNull();
    });

    it('treats blank text as missing', function (?string $text) {
        $setting = domainMakeSetting(['translations' => ['el' => $text]]);

        expect($setting->translation('el'))->toBeNull();
    })->with([[''], ['   '], [null]]);
});

describe('ordered()', function () {
    it('orders settings by sort_order', function () {
        domainMakeSetting(['key' => 'order_c', 'sort_order' => -1]);
        domainMakeSetting(['key' => 'order_a', 'sort_order' => -3]);
        domainMakeSetting(['key' => 'order_b', 'sort_order' => -2]);

        $keys = Setting::ordered()->whereIn('key', ['order_a', 'order_b', 'order_c'])->pluck('key')->all();

        expect($keys)->toBe(['order_a', 'order_b', 'order_c']);
    });
});

describe('toFormComponent()', function () {
    it('maps each setting type to its Filament input', function (SettingType $type, string $component) {
        $setting = domainMakeSetting(['type' => $type, 'key' => 'form_'.$type->value]);

        expect($setting->toFormComponent())->toBeInstanceOf($component);
    })->with([
        [SettingType::String, TextInput::class],
        [SettingType::Text, Textarea::class],
        [SettingType::Color, ColorPicker::class],
        [SettingType::Switch, Toggle::class],
        [SettingType::Number, TextInput::class],
        [SettingType::File, FileUpload::class],
        [SettingType::Select, Select::class],
        [SettingType::MultiSelect, Select::class],
    ]);

    it('names the input after the setting key and labels it', function () {
        $component = domainMakeSetting(['key' => 'prompt_username', 'label' => 'Username'])->toFormComponent();

        expect($component->getName())->toBe('prompt_username')
            ->and($component->getLabel())->toBe('Username');
    });

    it('offers the CV fonts for the font select', function () {
        $setting = domainMakeSetting(['key' => SettingKey::CvFont->value, 'type' => SettingType::Select]);

        expect($setting->toFormComponent()->getOptions())->toHaveKey('helvetica')->toHaveKey('dejavu-sans');
    });

    it('offers the additional CV languages, without the main language, for the languages select', function () {
        $setting = domainMakeSetting(['key' => SettingKey::CvLocales->value, 'type' => SettingType::MultiSelect]);
        $options = $setting->toFormComponent()->getOptions();

        expect($options)->toHaveKey('el')
            ->and($options)->not->toHaveKey(config('app.locale'))
            ->and($setting->toFormComponent()->isMultiple())->toBeTrue();
    });

    it('stores uploads on the public disk under the settings directory', function () {
        $component = domainMakeSetting(['type' => SettingType::File, 'key' => 'ascii_art'])->toFormComponent();

        expect($component->getDiskName())->toBe('public')
            ->and($component->getDirectory())->toBe(Setting::UPLOAD_DIRECTORY)
            ->and($component->getAcceptedFileTypes())->toBe(['text/plain']);
    });

    it('restricts the favicon and share image upload types', function () {
        $favicon = domainMakeSetting(['type' => SettingType::File, 'key' => 'favicon'])->toFormComponent();
        $og = domainMakeSetting(['type' => SettingType::File, 'key' => 'seo_og_image'])->toFormComponent();

        expect($favicon->getAcceptedFileTypes())->toContain('image/png', 'image/svg+xml')
            ->and($og->getAcceptedFileTypes())->toBe(['image/png', 'image/jpeg', 'image/webp']);
    });
});

describe('toTranslationComponent()', function () {
    it('binds the input to the translations array for a locale', function () {
        $component = domainMakeSetting(['key' => SettingKey::Name->value])->toTranslationComponent('el');

        expect($component)->toBeInstanceOf(TextInput::class)
            ->and($component->getName())->toBe('translations.el.name');
    });

    it('uses a textarea for long text settings', function () {
        $component = domainMakeSetting(['key' => SettingKey::About->value, 'type' => SettingType::Text])->toTranslationComponent('el');

        expect($component)->toBeInstanceOf(Textarea::class)
            ->and($component->getName())->toBe('translations.el.about');
    });

    it('shows the built-in section title as placeholder for CV title settings', function () {
        $component = domainMakeSetting(['key' => SettingKey::CvTitleSkills->value])->toTranslationComponent('el');

        expect($component->getPlaceholder())->toBe(trans('cv.sections.skills', [], 'el'));
    });
});
