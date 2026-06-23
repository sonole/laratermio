<?php

namespace App\Models;

use App\Enums\InteractionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $command_class
 * @property string $display_label
 * @property string|null $description
 * @property bool $is_enabled
 * @property InteractionType|null $interaction_type
 */
#[Fillable(['name', 'command_class', 'display_label', 'description', 'is_enabled', 'interaction_type'])]
class TerminalCommand extends Model
{
    protected function casts(): array
    {
        return [
            'is_enabled'       => 'boolean',
            'interaction_type' => InteractionType::class,
        ];
    }
}
