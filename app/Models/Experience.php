<?php

namespace App\Models;

use App\Models\Concerns\HasActiveOrder;
use App\Models\Concerns\HasTranslations;
use Carbon\Carbon;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;

/**
 * @property int $id
 * @property string $title
 * @property string $company
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property bool $is_current
 * @property array<int, string>|null $bullets
 * @property int $sort_order
 * @property bool $is_active
 * @property-read string $period
 */
#[Fillable(['title', 'company', 'start_date', 'end_date', 'is_current', 'bullets', 'sort_order', 'is_active'])]
class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasActiveOrder, HasFactory, HasTranslations;

    public static function translatableFields(): array
    {
        return ['title', 'company', 'bullets'];
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
            'bullets' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getPeriodAttribute(): string
    {
        $start = $this->start_date->translatedFormat('M Y');
        $end = $this->end_date ? $this->end_date->translatedFormat('M Y') : Lang::string('cv.present');

        return "{$start} – {$end}";
    }
}
