<?php

namespace App\Models;

use App\Enums\InteractionType;
use App\Models\Concerns\HasActive;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $command_class
 * @property string $display_label
 * @property string|null $description
 * @property bool $is_active
 * @property InteractionType|null $interaction_type
 */
#[Fillable(['name', 'command_class', 'display_label', 'description', 'is_active', 'interaction_type'])]
class TerminalCommand extends Model
{
    use HasActive;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'interaction_type' => InteractionType::class,
        ];
    }
}
