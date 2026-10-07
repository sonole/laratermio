<?php

namespace App\Models;

use App\Models\Concerns\HasActiveOrder;
use App\Models\Concerns\HasTranslations;
use Carbon\Carbon;
use Database\Factories\EducationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;

/**
 * @property int $id
 * @property string $title
 * @property string $institution
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property bool $is_certification
 * @property string|null $description
 * @property string|null $certificate_url
 * @property int $sort_order
 * @property bool $is_active
 * @property-read string $period
 */
#[Fillable(['title', 'institution', 'start_date', 'end_date', 'is_certification', 'description', 'certificate_url', 'sort_order', 'is_active'])]
class Education extends Model
{
    /** @use HasFactory<EducationFactory> */
    use HasActiveOrder, HasFactory, HasTranslations;

    protected $table = 'educations';

    public static function translatableFields(): array
    {
        return ['title', 'institution', 'description'];
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_certification' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getPeriodAttribute(): string
    {
        $start = $this->start_date->translatedFormat('M Y');
        $end = $this->end_date ? $this->end_date->translatedFormat('M Y') : ($this->is_certification ? null : Lang::string('cv.present'));

        return $end ? "{$start} – {$end}" : Lang::string('cv.issued_on', ['date' => $start]);
    }
}
