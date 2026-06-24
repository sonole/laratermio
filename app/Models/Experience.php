<?php

namespace App\Models;

use App\Models\Concerns\HasActiveOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
    use HasActiveOrder;

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
        $start = $this->start_date->format('M Y');
        $end = $this->end_date ? $this->end_date->format('M Y') : 'Present';

        return "{$start} – {$end}";
    }
}
