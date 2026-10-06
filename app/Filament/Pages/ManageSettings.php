<?php

namespace App\Filament\Pages;

use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Models\Setting;
use App\Services\CvLocales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/** @property-read Schema $form */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $slug = 'settings';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::query()->get();

        $values = $settings->pluck('value', 'key')->toArray();

        foreach ($settings as $setting) {
            $values[$setting->key] = match ($setting->type) {
                // Convert stored '1'/'0' strings to booleans for Toggle fields
                SettingType::Switch => $setting->value === '1',
                // Multi-selects are stored as a JSON list
                SettingType::MultiSelect => json_decode($setting->value ?? '[]', true) ?: [],
                default => $setting->value,
            };
        }

        $values['translations'] = $this->translationValues($settings);

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        $settings = Setting::ordered()->get();

        // Order by sort_order only so groups appear in the intended sequence
        // (Identity 0-9, About 10-19, Terminal 19-29, SEO 30+)
        // The CV section titles are left out: they live in the default tab of "CV translations".
        $sections = $settings
            ->reject(fn (Setting $s): bool => $this->isCvTitle($s))
            ->groupBy('group')
            ->map(fn ($settings, string $group) => Section::make($group)
                ->columns(2)
                ->schema($settings->map(fn (Setting $s) => $s->toFormComponent())->toArray())
            )
            ->values()
            ->toArray();

        return $schema->components([...$sections, ...$this->translationSections($settings)])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $translations = $data['translations'] ?? [];
        unset($data['translations']);

        foreach ($data as $key => $value) {
            // Convert boolean back to '1'/'0' for uniform string storage
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            // Multi-selects are stored as a JSON list
            if (is_array($value)) {
                $value = json_encode(array_values($value));
            }

            Setting::query()->where('key', $key)->update(['value' => $value]);
        }

        $this->saveTranslations($translations);

        // Re-sync the form with what was stored (normalised language list, cleaned-up translations).
        $this->mount();

        Notification::make()->title('Settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    /**
     * The CV text section: a default tab for the main language's section titles, then one tab per
     * extra CV language with an input for every translatable setting.
     *
     * @param  Collection<int, Setting>  $settings
     * @return list<Section>
     */
    private function translationSections(Collection $settings): array
    {
        $locales = app(CvLocales::class);
        $translatable = $settings->filter(fn (Setting $s): bool => SettingKey::tryFrom($s->key)?->isTranslatable() === true);

        if ($translatable->isEmpty()) {
            return [];
        }

        $titles = $settings->filter(fn (Setting $s): bool => $this->isCvTitle($s));

        // Every selectable language gets a tab that shows itself while the language picker (read
        // live, not from the saved setting) has it chosen. Hidden tabs are neither rendered nor
        // saved, so a tab appears the moment a language is picked and goes away when it is removed.
        $tabs = array_map(
            fn (string $locale): Tab => Tab::make($locales->name($locale))
                ->visible(fn (Get $get): bool => in_array($locale, $locales->only($get(SettingKey::CvLocales->value)), true))
                ->schema($translatable->map(fn (Setting $s) => $s->toTranslationComponent($locale))->values()->all()),
            array_keys($locales->selectable()),
        );

        // The main language is not a translation: its tab holds the title settings themselves.
        if ($titles->isNotEmpty()) {
            array_unshift($tabs, Tab::make($locales->name($locales->base()).' (default)')
                ->schema($titles->map(fn (Setting $s) => $s->toFormComponent())->values()->all()));
        }

        return [
            Section::make('CV translations')
                ->description('The default tab holds the CV section titles in the main language. Other languages translate them, plus your name, role and about text. Anything left empty falls back to the main-language text, or to the built-in section title.')
                ->schema([Tabs::make('cv-translations')->tabs($tabs)]),
        ];
    }

    /** The CV section titles (Objective, Experience, ...): main-language text that can also be translated. */
    private function isCvTitle(Setting $setting): bool
    {
        return SettingKey::tryFrom($setting->key)?->cvSection() !== null;
    }

    /**
     * Stored translations shaped for the form: `[locale => [setting key => text]]`.
     *
     * @param  Collection<int, Setting>  $settings
     * @return array<string, array<string, string|null>>
     */
    private function translationValues(Collection $settings): array
    {
        $values = [];

        // Every selectable language, not just the saved ones: a language can be switched on in the
        // form before saving, and it must show (and keep) the text stored for it earlier.
        foreach (array_keys(app(CvLocales::class)->selectable()) as $locale) {
            foreach ($settings as $setting) {
                if (SettingKey::tryFrom($setting->key)?->isTranslatable()) {
                    $values[$locale][$setting->key] = $setting->translations[$locale] ?? null;
                }
            }
        }

        return $values;
    }

    /**
     * Merge submitted translations into each setting. Only the languages that were on the form are
     * touched, so text for a language that is switched off right now is kept.
     *
     * @param  array<string, array<string, string|null>>  $translations
     */
    private function saveTranslations(array $translations): void
    {
        if ($translations === []) {
            return;
        }

        $keys = array_map(fn (SettingKey $key) => $key->value, SettingKey::translatable());

        foreach (Setting::query()->whereIn('key', $keys)->get() as $setting) {
            $merged = $setting->translations ?? [];

            foreach ($translations as $locale => $texts) {
                $text = $texts[$setting->key] ?? null;

                if (blank($text)) {
                    unset($merged[$locale]);
                } else {
                    $merged[$locale] = $text;
                }
            }

            $setting->translations = $merged === [] ? null : $merged;
            $setting->save();
        }
    }
}
