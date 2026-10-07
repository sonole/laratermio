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

    /*
    |--------------------------------------------------------------------------
    | Contact form limits
    |--------------------------------------------------------------------------
    |
    | The `contact` terminal command emails a confirmation to whatever address a visitor types,
    | so it is capped to keep it from being used to send mail to other people. Besides the
    | existing one-message-per-address-per-day rule: messages per visitor IP per hour, and
    | messages from everyone per day. Set a limit to 0 to switch it off.
    |
    */

    'contact' => [
        'per_ip_per_hour' => (int) env('CONTACT_MAX_PER_IP_PER_HOUR', 3),
        'per_day' => (int) env('CONTACT_MAX_PER_DAY', 50),
    ],

];
