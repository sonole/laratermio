<?php

namespace App\Services;

use App\Facades\Settings;
use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CvService
{
    public const string DISK = 'public';

    public const string PATH = 'uploads/cv/cv.pdf';

    public function generate(): void
    {
        $pdf = Pdf::loadView('cv', self::getVariables(forPdf: true))
            ->setPaper('a4');

        Storage::disk(self::DISK)->put(self::PATH, $pdf->output());
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

    /** @return array<string, mixed> */
    public static function getVariables(bool $forPdf = false): array
    {
        return [
            'forPdf' => $forPdf,
            'name' => Settings::getName(),
            'role' => Settings::getRole(),
            'about' => Settings::getAbout(),
            'contactItems' => ContactItem::activeOrdered()->get(),
            'experiences' => Experience::activeOrdered()->get(),
            'educations' => Education::activeOrdered()->get(),
            'skillCategories' => SkillCategory::activeOrdered()->get(),
            'projects' => Project::activeOrdered()->get(),
        ];
    }
}
