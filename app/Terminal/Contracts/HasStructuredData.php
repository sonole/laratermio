<?php

namespace App\Terminal\Contracts;

interface HasStructuredData
{
    /**
     * @return array<int, array{n: int, name: string, subtitle: string, html: string}>
     */
    public function structuredData(): array;
}