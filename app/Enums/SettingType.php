<?php

namespace App\Enums;

enum SettingType: string
{
    case String = 'string';
    case Text = 'text';
    case Url = 'url';
    case Color = 'color';
    case Switch = 'switch';
    case Number = 'number';
    case File = 'file';
}
