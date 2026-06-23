<?php

namespace App\Enums;

enum InteractionType: string
{
    case Paginate = 'paginate';
    case Selector = 'selector';

    public function label(): string
    {
        return match ($this) {
            self::Paginate => 'Paginate (one at a time)',
            self::Selector => 'Selector (arrow keys)',
        };
    }

}
