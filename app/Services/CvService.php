<?php

namespace App\Services;

use App\Enums\CvSection;
use App\Facades\Settings;
use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class CvService
{
    public const string DISK = 'public';

    public const string PATH = 'uploads/cv/cv.pdf';

    public function __construct(private readonly CvLocales $locales) {}

    /**
     * Generate the public CV: the main-language PDF stored for `/cv`. Other languages are never
     * stored; they are rendered on demand with {@see self::render()} and downloaded.
     */
    public function generate(): void
    {
        Storage::disk(self::DISK)->put(self::PATH, $this->render());
    }

    /**
     * Render the CV to PDF bytes without storing it.
     *
     * @param  string|null  $locale  A language switched on in settings; the main language by default.
     */
    public function render(?string $locale = null): string
    {
        // Do not enable `enable_font_subsetting`: the bundled Font Awesome file is OpenType-CFF
        // and DomPDF's subsetter corrupts it (icons turn into wrong glyphs or disappear). The
        // cost of embedding whole fonts is ~1 MB, and only for CVs that need DejaVu.
        return Pdf::loadHTML($this->html($locale))
            ->setPaper('a4')
            ->output();
    }

    /**
     * The CV as HTML, with text, dates and section titles in `$locale`.
     *
     * @param  string|null  $locale  A language switched on in settings; the main language by default.
     */
    public function html(?string $locale = null): string
    {
        $locale ??= $this->locales->base();

        if (! $this->locales->isEnabled($locale)) {
            throw new InvalidArgumentException("CV language [$locale] is not enabled.");
        }

        return $this->withLocale($locale, fn (): string => view('cv', self::getVariables(forPdf: true, locale: $locale))->render());
    }

    /**
     * Run `$callback` with translations (`__()`) and dates (month names) in `$locale`.
     *
     * Deliberately not `app()->setLocale()`: that overwrites `config('app.locale')`, which is what
     * identifies the main content language.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function withLocale(string $locale, Closure $callback): mixed
    {
        $translator = app('translator');
        $translatorLocale = $translator->getLocale();
        $dateLocale = Date::getLocale();

        $translator->setLocale($locale);
        Date::setLocale($locale);

        try {
            return $callback();
        } finally {
            $translator->setLocale($translatorLocale);
            Date::setLocale($dateLocale);
        }
    }

    /** File name for a downloaded CV, e.g. `alex-example_cv.pdf` or `alex-example_cv_el.pdf`. */
    public function filename(?string $locale = null): string
    {
        $locale ??= $this->locales->base();
        $suffix = $this->locales->isBase($locale) ? '' : "_$locale";

        return str(Settings::getName($locale))->slug()->append("_cv$suffix.pdf")->value();
    }

    public function exists(): bool
    {
        return Storage::disk(self::DISK)->exists(self::PATH);
    }

    public function lastGeneratedAt(): ?Carbon
    {
        if (! $this->exists()) {
            return null;
        }

        return Carbon::createFromTimestamp(
            Storage::disk(self::DISK)->lastModified(self::PATH)
        );
    }

    /**
     * Everything the `cv` view needs, written in `$locale` (the main language by default).
     *
     * @return array<string, mixed>
     */
    public static function getVariables(bool $forPdf = false, ?string $locale = null): array
    {
        $locale ??= config('app.locale');

        $variables = [
            'forPdf' => $forPdf,
            'locale' => $locale,
            'sectionTitles' => self::sectionTitles($locale),
            'name' => Settings::getName($locale),
            'role' => Settings::getRole($locale),
            'about' => Settings::getAbout($locale),
            'contactItems' => ContactItem::activeOrdered()->get()->map->localized($locale),
            'experiences' => Experience::activeOrdered()->get()->map->localized($locale),
            'educations' => Education::activeOrdered()->get()->map->localized($locale),
            'skillCategories' => SkillCategory::activeOrdered()->get()->map->localized($locale),
            'projects' => Project::activeOrdered()->get()->map->localized($locale),
        ];

        // Judge the font on the actual content: a Latin-only font is swapped for its DejaVu
        // equivalent only when the CV really contains characters it cannot draw.
        $content = json_encode($variables, JSON_UNESCAPED_UNICODE) ?: '';

        $variables['fontStack'] = Settings::getCvFont()->resolveFor($content)->cssStack();

        return $variables;
    }

    /**
     * Section titles for a language: the admin's override if there is one, otherwise the built-in
     * title from `lang/<locale>/cv.php`.
     *
     * @return array<string, string> section key => title
     */
    public static function sectionTitles(string $locale): array
    {
        $titles = [];

        foreach (CvSection::cases() as $section) {
            $titles[$section->value] = Settings::getTranslated($section->settingKey(), $locale)
                ?? $section->defaultTitle($locale);
        }

        return $titles;
    }
}
