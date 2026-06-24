<?php

namespace App\Models;

use App\Models\Concerns\HasActiveOrder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property string $name
 * @property string|null $subtitle
 * @property string|null $video_url
 * @property array<int, string>|null $tech
 * @property array<int, string>|null $bullets
 * @property array<int, array{label: string, url: string}>|null $links
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['name', 'subtitle', 'video_url', 'tech', 'bullets', 'links', 'sort_order', 'is_active'])]
class Project extends Model implements HasMedia
{
    use HasActiveOrder, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'tech' => 'array',
            'bullets' => 'array',
            'links' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

        $this->addMediaCollection('gallery')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

        $this->addMediaCollection('video_file')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes(['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm']);
    }

    public function imageUrl(): string
    {
        return $this->getFirstMediaUrl('main_image');
    }

    /** @return array<int, string> */
    public function galleryUrls(): array
    {
        return $this->getMedia('gallery')->map(fn ($media) => $media->getUrl())->all();
    }

    public function videoFileUrl(): ?string
    {
        $media = $this->getFirstMedia('video_file');

        return $media?->getUrl();
    }
}
