<?php

namespace App\Models;

use App\Models\Concerns\HasActiveOrder;
use App\Models\Concerns\HasTranslations;
use Database\Factories\SkillCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property array<int, string>|null $items
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['name', 'items', 'sort_order', 'is_active'])]
class SkillCategory extends Model
{
    /** @use HasFactory<SkillCategoryFactory> */
    use HasActiveOrder, HasFactory, HasTranslations;

    public static function translatableFields(): array
    {
        return ['name', 'items'];
    }

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
