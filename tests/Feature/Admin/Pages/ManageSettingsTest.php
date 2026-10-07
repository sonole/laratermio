<?php

use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    // The upload fields write to the public disk: keep them away from the real one and from other tests.
    $this->disk = storage_path('framework/testing/disks/admin-pages');
    File::deleteDirectory($this->disk);
    config(['filesystems.disks.public.root' => $this->disk]);
    Storage::forgetDisk('public');

    actingAsAdmin();
    seedSystem();
});

afterEach(function () {
    File::deleteDirectory($this->disk);
    Storage::forgetDisk('public');
});

function adminSettingValue(SettingKey $key): ?string
{
    return Setting::query()->where('key', $key->value)->value('value');
}

describe('settings page', function () {
    it('renders for a signed-in admin', function () {
        $this->get('/admin/settings')->assertOk();
    });

    it('is in the Content navigation group', function () {
        expect(ManageSettings::getNavigationLabel())->toBe('Settings')
            ->and(ManageSettings::getNavigationGroup())->toBe('Content')
            ->and(ManageSettings::getUrl())->toEndWith('/admin/settings');
    });

    it('groups the settings into one section per group, in order', function () {
        Livewire::test(ManageSettings::class)
            ->assertSeeInOrder(['Identity', 'Terminal Prompt', 'SEO', 'CV'])
            ->assertSee('CV translations');
    });

    it('shows an input for every setting, of the right kind', function () {
        Livewire::test(ManageSettings::class)
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('about')
            ->assertFormFieldExists('ascii_art_enabled')
            ->assertFormFieldExists('ascii_art')
            ->assertFormFieldExists('ascii_art_size')
            ->assertFormFieldExists('ascii_art_color')
            ->assertFormFieldExists('prompt_username')
            ->assertFormFieldExists('prompt_hostname_color')
            ->assertFormFieldExists('seo_title')
            ->assertFormFieldExists('seo_description')
            ->assertFormFieldExists('favicon')
            ->assertFormFieldExists('seo_og_image')
            ->assertFormFieldExists('seo_twitter_handle')
            ->assertFormFieldExists('cv_font')
            ->assertFormFieldExists('cv_locales');
    });

    it('uses the setting type to pick the input', function () {
        $page = Livewire::test(ManageSettings::class);

        foreach ([
            'name' => TextInput::class,
            'about' => Textarea::class,
            'ascii_art_enabled' => Toggle::class,
            'ascii_art' => FileUpload::class,
            'ascii_art_size' => TextInput::class,
            'ascii_art_color' => ColorPicker::class,
            'cv_font' => Select::class,
        ] as $key => $component) {
            $page->assertFormFieldExists($key, checkFieldUsing: fn ($field) => $field instanceof $component);
        }
    });

    it('fills the form from the stored values', function () {
        cvSetting(SettingKey::Name, 'Alex Example');
        cvSetting(SettingKey::PromptHostname, 'alex.dev');

        Livewire::test(ManageSettings::class)
            ->assertFormSet(['name' => 'Alex Example', 'prompt_hostname' => 'alex.dev']);
    });

    it('shows switches as on or off according to the stored "1" or "0"', function () {
        Setting::query()->where('key', 'ascii_art_enabled')->update(['value' => '1']);

        Livewire::test(ManageSettings::class)->assertFormSet(['ascii_art_enabled' => true]);

        Setting::query()->where('key', 'ascii_art_enabled')->update(['value' => '0']);

        Livewire::test(ManageSettings::class)->assertFormSet(['ascii_art_enabled' => false]);

        Setting::query()->where('key', 'ascii_art_enabled')->update(['value' => null]);

        Livewire::test(ManageSettings::class)->assertFormSet(['ascii_art_enabled' => false]);
    });
});

describe('saving settings', function () {
    it('saves text settings', function () {
        Livewire::test(ManageSettings::class)
            ->fillForm([
                'name' => 'Alexandros',
                'role' => 'Staff Engineer',
                'about' => "Line one\nLine two",
                'prompt_username' => 'guest',
                'seo_title' => 'My Portfolio',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Settings saved');

        expect(adminSettingValue(SettingKey::Name))->toBe('Alexandros')
            ->and(adminSettingValue(SettingKey::Role))->toBe('Staff Engineer')
            ->and(adminSettingValue(SettingKey::About))->toBe("Line one\nLine two")
            ->and(adminSettingValue(SettingKey::PromptUsername))->toBe('guest')
            ->and(adminSettingValue(SettingKey::SeoTitle))->toBe('My Portfolio');
    });

    it('stores switches as "1" and "0"', function () {
        $page = Livewire::test(ManageSettings::class)
            ->fillForm(['ascii_art_enabled' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(adminSettingValue(SettingKey::AsciiArtEnabled))->toBe('1');

        $page->fillForm(['ascii_art_enabled' => false])->call('save');

        expect(adminSettingValue(SettingKey::AsciiArtEnabled))->toBe('0');
        $page->assertFormSet(['ascii_art_enabled' => false]);
    });

    it('saves colours and numbers', function () {
        Livewire::test(ManageSettings::class)
            ->fillForm([
                'ascii_art_color' => '#ff8800',
                'prompt_username_color' => '#112233',
                'ascii_art_size' => '1.25',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(adminSettingValue(SettingKey::AsciiArtColor))->toBe('#ff8800')
            ->and(adminSettingValue(SettingKey::PromptUsernameColor))->toBe('#112233')
            ->and(adminSettingValue(SettingKey::AsciiArtSize))->toBe('1.25');
    });

    it('stores an emptied field as null', function () {
        cvSetting(SettingKey::SeoTwitterHandle, '@alex');

        Livewire::test(ManageSettings::class)
            ->fillForm(['seo_twitter_handle' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(adminSettingValue(SettingKey::SeoTwitterHandle))->toBeNull();
    });

    it('keeps the saved values on the form after saving', function () {
        Livewire::test(ManageSettings::class)
            ->fillForm(['name' => 'Alexandros'])
            ->call('save')
            ->assertFormSet(['name' => 'Alexandros']);
    });

    it('leaves settings the form does not edit as they were', function () {
        cvSetting(SettingKey::CvTitleSkills, 'Toolbox');
        $before = Setting::query()->count();

        Livewire::test(ManageSettings::class)->fillForm(['name' => 'Alexandros'])->call('save');

        expect(Setting::query()->count())->toBe($before)
            ->and(adminSettingValue(SettingKey::CvTitleSkills))->toBe('Toolbox');
    });
});

describe('settings validation', function () {
    it('rejects a number of zero or below', function (string $value) {
        Livewire::test(ManageSettings::class)
            ->fillForm(['ascii_art_size' => $value])
            ->call('save')
            ->assertHasFormErrors(['ascii_art_size']);

        expect(adminSettingValue(SettingKey::AsciiArtSize))->toBeNull();
    })->with(['zero' => '0', 'negative' => '-1']);

    it('rejects text where a number is expected', function () {
        Livewire::test(ManageSettings::class)
            ->fillForm(['ascii_art_size' => 'big'])
            ->call('save')
            ->assertHasFormErrors(['ascii_art_size']);
    });

    it('saves nothing when one field is invalid', function () {
        Livewire::test(ManageSettings::class)
            ->fillForm(['name' => 'Should Not Save', 'ascii_art_size' => '-5'])
            ->call('save')
            ->assertHasFormErrors(['ascii_art_size']);

        expect(adminSettingValue(SettingKey::Name))->not->toBe('Should Not Save');
    });
});

describe('file settings', function () {
    it('stores an uploaded file on the public disk and saves its path', function () {
        $file = UploadedFile::fake()->createWithContent('art.txt', "  /\\_/\\\n ( o.o )");

        Livewire::test(ManageSettings::class)
            ->fillForm(['ascii_art' => $file])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = adminSettingValue(SettingKey::AsciiArt);

        expect($path)->toStartWith('uploads/settings/')->toEndWith('.txt')
            ->and(Storage::disk('public')->exists($path))->toBeTrue();
    });

    it('stores an uploaded image', function () {
        Livewire::test(ManageSettings::class)
            ->fillForm(['seo_og_image' => UploadedFile::fake()->image('share.png', 600, 315)])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = adminSettingValue(SettingKey::SeoOgImage);

        expect($path)->toStartWith('uploads/settings/')
            ->and(Storage::disk('public')->exists($path))->toBeTrue();
    });

    it('only accepts the right file types', function (string $key, UploadedFile $file) {
        Livewire::test(ManageSettings::class)
            ->fillForm([$key => $file])
            ->call('save')
            ->assertHasFormErrors([$key]);

        expect(adminSettingValue(SettingKey::from($key)))->toBeNull();
    })->with([
        'ASCII art must be plain text' => fn () => ['ascii_art', UploadedFile::fake()->image('art.png')],
        'social image must be an image' => fn () => ['seo_og_image', UploadedFile::fake()->create('share.pdf', 10, 'application/pdf')],
        'favicon must be an icon' => fn () => ['favicon', UploadedFile::fake()->create('icon.pdf', 10, 'application/pdf')],
    ]);

    it('keeps the stored file path when saving other settings', function () {
        Storage::disk('public')->put('uploads/settings/art.txt', 'ascii');
        cvSetting(SettingKey::AsciiArt, 'uploads/settings/art.txt')->update(['type' => SettingType::File]);

        Livewire::test(ManageSettings::class)
            ->fillForm(['name' => 'Alexandros'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(adminSettingValue(SettingKey::AsciiArt))->toBe('uploads/settings/art.txt');
    });
});
