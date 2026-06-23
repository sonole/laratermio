<?php

namespace App\Facades;

use App\Services\SettingsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null get(string $key, ?string $default = null)
 * @method static bool getBool(string $key, bool $default = false)
 * @method static float|null getFloat(string $key, ?float $default = null)
 * @method static string faviconUrl()
 * @method static string getName()
 * @method static string getRole()
 * @method static string getPromptHostname()
 * @method static string getPrompt(bool $pretty = false)
 * @method static string getPromptWithCwd(string $cwd, bool $pretty = false)
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingsManager::class;
    }
}
