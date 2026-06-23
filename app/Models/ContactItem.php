<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $icon
 * @property string $label
 * @property string|null $url
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['icon', 'label', 'url', 'sort_order', 'is_active'])]
class ContactItem extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Maps Font Awesome icon classes to a terminal key and an admin display label.
     *
     * @return array<string, array{key: string, label: string}>
     */
    public static function iconAliases(): array
    {
        return [
            'fa-solid fa-envelope' => ['key' => 'email', 'label' => 'Email'],
            'fa-solid fa-phone' => ['key' => 'phone', 'label' => 'Phone'],
            'fa-solid fa-location-dot' => ['key' => 'location', 'label' => 'Location'],
            'fa-solid fa-globe' => ['key' => 'website', 'label' => 'Website'],
            'fa-solid fa-link' => ['key' => 'link', 'label' => 'Link'],
            'fa-brands fa-linkedin' => ['key' => 'linkedin', 'label' => 'LinkedIn'],
            'fa-brands fa-github' => ['key' => 'github', 'label' => 'GitHub'],
            'fa-brands fa-x-twitter' => ['key' => 'twitter', 'label' => 'X (Twitter)'],
            'fa-brands fa-twitter' => ['key' => 'twitter', 'label' => 'Twitter'],
            'fa-brands fa-instagram' => ['key' => 'instagram', 'label' => 'Instagram'],
            'fa-brands fa-youtube' => ['key' => 'youtube', 'label' => 'YouTube'],
            'fa-brands fa-facebook' => ['key' => 'facebook', 'label' => 'Facebook'],
            'fa-brands fa-discord' => ['key' => 'discord', 'label' => 'Discord'],
            'fa-brands fa-telegram' => ['key' => 'telegram', 'label' => 'Telegram'],
            'fa-brands fa-whatsapp' => ['key' => 'whatsapp', 'label' => 'WhatsApp'],
            'fa-brands fa-medium' => ['key' => 'medium', 'label' => 'Medium'],
            'fa-brands fa-dev' => ['key' => 'dev', 'label' => 'DEV.to'],
            'fa-brands fa-stack-overflow' => ['key' => 'stackoverflow', 'label' => 'Stack Overflow'],
            'fa-brands fa-behance' => ['key' => 'behance', 'label' => 'Behance'],
            'fa-brands fa-dribbble' => ['key' => 'dribbble', 'label' => 'Dribbble'],
        ];
    }

    /** Terminal-matching key derived from the icon class, or null if unknown. */
    public function iconAlias(): ?string
    {
        if (! $this->icon) {
            return null;
        }

        return static::iconAliases()[$this->icon]['key'] ?? null;
    }
}
