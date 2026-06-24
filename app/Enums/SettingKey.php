<?php

namespace App\Enums;

enum SettingKey: string
{
    case Name = 'name';
    case Role = 'role';
    case About = 'about';
    case AsciiArtEnabled = 'ascii_art_enabled';
    case AsciiArt = 'ascii_art';
    case AsciiArtSize = 'ascii_art_size';
    case AsciiArtColor = 'ascii_art_color';
    case PromptUsername = 'prompt_username';
    case PromptUsernameColor = 'prompt_username_color';
    case PromptHostname = 'prompt_hostname';
    case PromptHostnameColor = 'prompt_hostname_color';
    case PromptSeparatorColor = 'prompt_separator_color';
    case SeoTitle = 'seo_title';
    case SeoDescription = 'seo_description';
    case Favicon = 'favicon';
    case SeoOgImage = 'seo_og_image';
    case SeoTwitterHandle = 'seo_twitter_handle';
}
