<?php

namespace App\Enums;

use Illuminate\Support\Facades\Lang;

/** The titled sections of the CV. Their wording comes from `lang/<locale>/cv.php` or an admin override. */
enum CvSection: string
{
    case Objective = 'objective';
    case Experience = 'experience';
    case Education = 'education';
    case Skills = 'skills';
    case Projects = 'projects';

    /** The setting that lets an admin replace this section's title. */
    public function settingKey(): SettingKey
    {
        return match ($this) {
            self::Objective => SettingKey::CvTitleObjective,
            self::Experience => SettingKey::CvTitleExperience,
            self::Education => SettingKey::CvTitleEducation,
            self::Skills => SettingKey::CvTitleSkills,
            self::Projects => SettingKey::CvTitleProjects,
        };
    }

    /** The built-in title for a locale, from the language files. */
    public function defaultTitle(string $locale): string
    {
        return Lang::string('cv.sections.'.$this->value, [], $locale);
    }
}
