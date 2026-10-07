<?php

namespace App\Terminal\Contracts;

interface HasStructuredData
{
    /**
     * `name` and `subtitle` are already HTML-escaped: the selector injects them into the page as raw HTML.
     *
     * @return array<int, array{n: int, name: string, subtitle: string, html: string}>
     */
    public function structuredData(): array;
}
