<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Toggle::make('is_active')->label('Active')->default(true),
                        TextInput::make('name')->required(),
                        TextInput::make('subtitle'),
                        SpatieMediaLibraryFileUpload::make('main_image')
                            ->label('Main Image')
                            ->collection('main_image')
                            ->image()
                            ->helperText('Primary image shown in the project card.'),
                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->label('Gallery Images')
                            ->collection('gallery')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->helperText('Upload additional images for the project gallery.'),
                        SpatieMediaLibraryFileUpload::make('video_file')
                            ->label('Video File')
                            ->collection('video_file')
                            ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm', 'application/mp4'])
                            ->helperText('Upload a video file (.mp4, .mov, .webm). Optional.'),
                        TextInput::make('video_url')
                            ->label('Video URL (YouTube / Vimeo)')
                            ->url()
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->helperText('Paste a YouTube or Vimeo URL (optional).'),
                    ]),
                Section::make('Description')
                    ->schema([
                        Repeater::make('bullets')
                            ->label('Bullet points')
                            ->simple(
                                Textarea::make('value')->required()->rows(3),
                            )
                            ->reorderable()
                            ->addActionLabel('Add bullet'),
                        Repeater::make('links')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('label')->required()->placeholder('GitHub'),
                                    TextInput::make('url')->required()->url()->placeholder('https://github.com/...'),
                                ]),
                            ])
                            ->reorderable()
                            ->addActionLabel('Add link'),
                        Repeater::make('tech')
                            ->label('Technologies')
                            ->simple(
                                TextInput::make('value')->required(),
                            )
                            ->reorderable()
                            ->addActionLabel('Add technology'),
                    ]),
            ]);
    }
}
