<?php

namespace Database\Seeders;

class PersonalContentSeeder extends ContentSeeder
{
    /** @return array<string, mixed> */
    protected function config(): array
    {
        return [
            'settings' => [],
            'experiences' => [],
            'educations' => [],
            'projects' => [],
            'skills' => [],
            'contact_items' => [],
        ];
    }
}
