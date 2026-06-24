<?php

namespace App\Http\Controllers;

use App\Enums\SettingKey;
use App\Facades\Settings;
use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillCategory;
use App\Terminal\CommandRegistry;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(CommandRegistry $registry): View
    {
        $name = Settings::getName();
        $role = Settings::getRole();
        $ogImage = Settings::ogImageUrl();
        $seoDescription = Settings::get(SettingKey::SeoDescription);
        $canonicalUrl = rtrim(config('app.url'), '/');

        $contacts = ContactItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $jsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $name,
            'jobTitle' => $role,
            'description' => $seoDescription,
            'url' => $canonicalUrl,
            'image' => $ogImage,
            'sameAs' => $contacts
                ->filter(fn ($c) => str_starts_with($c->url ?? '', 'http'))
                ->pluck('url')
                ->values()
                ->all(),
        ]);

        return view('home', [
            'hasFastfetch' => $registry->resolve('fastfetch') !== null,
            'seo' => [
                'title' => Settings::get(SettingKey::SeoTitle, config('app.name')),
                'desc' => $seoDescription ?? '',
                'name' => $name,
                'role' => $role,
                'canonical' => $canonicalUrl,
                'og_image' => $ogImage,
                'twitter_handle' => Settings::get(SettingKey::SeoTwitterHandle),
                'json_ld' => $jsonLd,
            ],
            'faviconUrl' => Settings::faviconUrl(),
            'about' => Settings::getAbout(),
            'contacts' => $contacts,
            'experiences' => Experience::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'projects' => Project::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'skillCategories' => SkillCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'educations' => Education::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
