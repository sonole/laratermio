<?php

use App\Enums\CvFont;
use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Facades\Settings;
use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['must_change_password' => false]));
});

it('adds the CV font setting through a migration, defaulting to Helvetica', function () {
    $setting = Setting::query()->where('key', SettingKey::CvFont->value)->firstOrFail();

    expect($setting->type)->toBe(SettingType::Select)
        ->and($setting->group)->toBe('CV')
        ->and($setting->value)->toBe(CvFont::default()->value);
});

it('lets an admin choose the CV font on the settings page', function () {
    Livewire::test(ManageSettings::class)
        ->assertFormSet(['cv_font' => CvFont::default()->value])
        ->fillForm(['cv_font' => CvFont::DejaVuSerif->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::query()->where('key', 'cv_font')->value('value'))->toBe('dejavu-serif');
});

it('reads the saved font back through the settings manager', function () {
    Setting::query()->where('key', 'cv_font')->update(['value' => 'courier']);

    expect(Settings::getCvFont())->toBe(CvFont::Courier);
});

it('defaults to Helvetica when no font has been saved', function () {
    Setting::query()->where('key', 'cv_font')->delete();

    expect(Settings::getCvFont())->toBe(CvFont::Helvetica);
});
