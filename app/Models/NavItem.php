<?php

namespace App\Models;

use App\Enums\NavItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $terminal_command_id
 * @property string|null $command_args
 * @property string|null $label
 * @property string|null $url
 * @property string $target
 * @property NavItemType $type
 * @property int $sort_order
 * @property bool $is_active
 * @property-read TerminalCommand|null $terminalCommand
 */
#[Fillable(['terminal_command_id', 'command_args', 'label', 'url', 'target', 'type', 'sort_order', 'is_active'])]
class NavItem extends Model
{
    public const string UPLOAD_DIRECTORY = 'uploads/nav-items';

    protected function casts(): array
    {
        return [
            'type' => NavItemType::class,
            'is_active' => 'boolean',
        ];
    }

    public function terminalCommand(): BelongsTo
    {
        return $this->belongsTo(TerminalCommand::class);
    }

    /** Resolved display label — command label or manual label. */
    public function getDisplayLabel(): string
    {
        return match ($this->type) {
            NavItemType::Command => $this->terminalCommand?->display_label ?? '',
            NavItemType::Cv => $this->label ?? 'cv',
            default => $this->label ?? '',
        };
    }
}
