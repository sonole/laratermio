<?php

namespace App\Enums;

enum NavItemType: string
{
    case Command = 'command';
    case Link = 'link';
    case Cv = 'cv';

    public function label(): string
    {
        return match ($this) {
            self::Command => 'Command button',
            self::Link => 'Link button',
            self::Cv => 'CV button',
        };
    }
}
