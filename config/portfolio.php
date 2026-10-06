<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CV languages
    |--------------------------------------------------------------------------
    |
    | Languages a CV can be rendered in, as locale => native name. The site itself is
    | single-language: your main content fields are written in `app.locale`, and every other
    | language offered here only adds extra translation inputs in the admin panel and a
    | downloadable CV. Which of them are switched on is chosen in Settings → CV.
    |
    | Add a language by adding a line here and a `lang/<locale>/cv.php` file with its section
    | titles. Scripts DomPDF cannot shape (Arabic, Hebrew, Chinese, Japanese, Korean, Thai,
    | Devanagari…) are deliberately not listed: the bundled DejaVu fonts do not cover them.
    |
    */

    'cv_locales' => [
        'en' => 'English',
        'el' => 'Ελληνικά',
        'it' => 'Italiano',
        'de' => 'Deutsch',
        'sv' => 'Svenska',
        'nl' => 'Nederlands',
        'fr' => 'Français',
        'es' => 'Español',
        'pt' => 'Português',
        'da' => 'Dansk',
        'nb' => 'Norsk bokmål',
        'fi' => 'Suomi',
        'pl' => 'Polski',
        'cs' => 'Čeština',
        'ro' => 'Română',
        'tr' => 'Türkçe',
        'ru' => 'Русский',
        'uk' => 'Українська',
    ],

];
