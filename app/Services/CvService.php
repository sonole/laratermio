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
    public const string DISK = 'local';

    public const string PATH = 'cv/cv.pdf';

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

    public static function getVariables(bool $forPdf = false): array
    {
        return [
            'forPdf' => $forPdf,
            'name' => Settings::getName(),
            'role' => Settings::getRole(),
            'about' => Settings::get('about'),
            'contactItems' => ContactItem::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'experiences' => Experience::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'educations' => Education::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'skillCategories' => SkillCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'projects' => Project::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ];
    }
}
