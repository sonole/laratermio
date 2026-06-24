<?php

namespace App\Facades;

use App\Enums\SettingKey;
use App\Services\SettingsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null get(SettingKey $key, ?string $default = null)
 * @method static bool getBool(SettingKey $key, bool $default = false)
 * @method static float|null getFloat(SettingKey $key, ?float $default = null)
 * @method static string faviconUrl()
 * @method static string|null ogImageUrl()
 * @method static string getName()
 * @method static string getRole()
 * @method static string getAbout()
 * @method static string getPromptUsername()
 * @method static string getPromptUsernameColor()
 * @method static string getPromptHostname()
 * @method static string getPromptHostnameColor()
 * @method static string getPromptSeparatorColor()
 * @method static string getPromptSuffix(?string $cwd = null)
 * @method static string getPrompt(?string $cwd = null, bool $pretty = true)
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingsManager::class;
    }
}
