<?php

namespace App\Models;

use App\Enums\CvFont;
use App\Enums\CvSection;
use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Models\Concerns\HasOrder;
use App\Services\CvLocales;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string $label
 * @property SettingType $type
 * @property string|null $value
 * @property array<string, string|null>|null $translations Text per CV language, for translatable settings
 * @property int $sort_order
 */
#[Fillable(['group', 'key', 'label', 'type', 'value', 'translations', 'sort_order'])]
class Setting extends Model
{
    use HasOrder;

    public const string UPLOAD_DIRECTORY = 'uploads/settings';

    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'translations' => 'array',
        ];
    }

    /** The text for a CV language, or null when none was entered. */
    public function translation(string $locale): ?string
    {
        $text = $this->translations[$locale] ?? null;

        return blank($text) ? null : $text;
    }

    /** Return the appropriate Filament form component for this setting. */
    public function toFormComponent(): TextInput|Textarea|ColorPicker|Toggle|FileUpload|Select
    {
        return match ($this->type) {
            SettingType::Select => Select::make($this->key)
                ->label($this->label)
                ->options($this->selectOptions())
                ->helperText($this->selectHelperText())
                ->native(false)
                ->selectablePlaceholder(false),
            SettingType::MultiSelect => Select::make($this->key)
                ->label($this->label)
                ->options($this->selectOptions())
                ->helperText($this->selectHelperText())
                ->multiple()
                ->native(false)
                // Dependent sections (e.g. the CV translation tabs) react to the choice before saving.
                ->live()
                ->columnSpanFull(),
            SettingType::Text => Textarea::make($this->key)
                ->label($this->label)
                ->rows(10)
                ->columnSpanFull(),
            SettingType::Color => ColorPicker::make($this->key)
                ->label($this->label),
            SettingType::Switch => Toggle::make($this->key)
                ->label($this->label),
            SettingType::Number => TextInput::make($this->key)
                ->label($this->label)
                ->numeric()
                ->minValue(0.01)
                ->step(0.01),
            SettingType::File => (function () {
                $field = FileUpload::make($this->key)
                    ->label($this->label)
                    ->disk('public')
                    ->directory(self::UPLOAD_DIRECTORY)
                    ->columnSpanFull();

                return match ($this->key) {
                    'ascii_art' => $field->acceptedFileTypes(['text/plain']),
                    'favicon' => $field->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml', 'image/gif']),
                    'seo_og_image' => $field->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp']),
                    default => $field,
                };
            })(),
            default => $this->toTextInput($this->key, app(CvLocales::class)->base()),
        };
    }

    /** The input for this setting's text in one CV language, bound to `translations.<locale>.<key>`. */
    public function toTranslationComponent(string $locale): TextInput|Textarea
    {
        $name = "translations.$locale.$this->key";

        if ($this->type === SettingType::Text) {
            return Textarea::make($name)
                ->label($this->label)
                ->rows(10)
                ->helperText('Leave empty to use the main-language text.')
                ->columnSpanFull();
        }

        return $this->toTextInput($name, $locale)
            ->helperText($this->cvSection() ? 'Leave empty to use the default title.' : 'Leave empty to use the main-language text.');
    }

    private function toTextInput(string $name, string $locale): TextInput
    {
        $input = TextInput::make($name)->label($this->label);

        // Title settings show the built-in title as a hint, so "empty" visibly means "use this".
        if ($section = $this->cvSection()) {
            $input->placeholder($section->defaultTitle($locale));
        }

        return $input;
    }

    private function cvSection(): ?CvSection
    {
        return SettingKey::tryFrom($this->key)?->cvSection();
    }

    /** @return array<string, string> */
    private function selectOptions(): array
    {
        return match ($this->key) {
            SettingKey::CvFont->value => CvFont::options(),
            SettingKey::CvLocales->value => app(CvLocales::class)->selectable(),
            default => [],
        };
    }

    private function selectHelperText(): ?string
    {
        return match ($this->key) {
            SettingKey::CvFont->value => 'Latin-only fonts switch to the matching DejaVu font automatically when the CV contains Greek, Cyrillic or other non-Latin text.',
            SettingKey::CvLocales->value => $this->cvLocalesHelperText(),
            default => null,
        };
    }

    private function cvLocalesHelperText(): string
    {
        $base = app(CvLocales::class)->name(app(CvLocales::class)->base());

        return "Your main content is written in {$base}. Each language chosen here adds translation inputs to the settings and to every CV section. "
            .'The public site and the public CV always stay in the main language; other languages are generated and downloaded from the dashboard.';
    }
}
