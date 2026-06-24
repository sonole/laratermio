<?php

namespace App\Models;

use App\Enums\SettingType;
use App\Models\Concerns\HasOrder;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
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
 * @property int $sort_order
 */
#[Fillable(['group', 'key', 'label', 'type', 'value', 'sort_order'])]
class Setting extends Model
{
    use HasOrder;

    public const string UPLOAD_DIRECTORY = 'uploads/settings';

    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
        ];
    }

    /** Return the appropriate Filament form component for this setting. */
    public function toFormComponent(): TextInput|Textarea|ColorPicker|Toggle|FileUpload
    {
        return match ($this->type) {
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
            default => TextInput::make($this->key)
                ->label($this->label),
        };
    }
}
