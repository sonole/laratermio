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
    case CvFont = 'cv_font';
    case CvLocales = 'cv_locales';
    case CvTitleObjective = 'cv_title_objective';
    case CvTitleExperience = 'cv_title_experience';
    case CvTitleEducation = 'cv_title_education';
    case CvTitleSkills = 'cv_title_skills';
    case CvTitleProjects = 'cv_title_projects';

    /**
     * Settings that can hold a different text per CV language.
     *
     * @return list<self>
     */
    public static function translatable(): array
    {
        return [
            self::Name,
            self::Role,
            self::About,
            self::CvTitleObjective,
            self::CvTitleExperience,
            self::CvTitleEducation,
            self::CvTitleSkills,
            self::CvTitleProjects,
        ];
    }

    public function isTranslatable(): bool
    {
        return in_array($this, self::translatable(), true);
    }

    /** The CV section whose title this setting overrides, if it is a title setting. */
    public function cvSection(): ?CvSection
    {
        foreach (CvSection::cases() as $section) {
            if ($section->settingKey() === $this) {
                return $section;
            }
        }

        return null;
    }
}
