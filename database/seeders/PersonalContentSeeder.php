<?php

namespace Database\Seeders;

class PersonalContentSeeder extends ContentSeeder
{
    protected function config(): array
    {
        return [
            'settings' => [
                'Identity' => [],
                'Terminal Prompt' => [],
                'SEO' => [],
            ],
            'experiences' => [],
            'educations' => [],
            'projects' => [],
            'skills' => [],
            'contact_items' => [],
        ];
    }
}
