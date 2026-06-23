<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $name }} – CV</title>
    @if ($forPdf ?? false)
        <style>
            @font-face {
                font-family: "Font Awesome 7 Free";
                font-weight: 900;
                font-style: normal;
                src: url("file://{{ public_path('fonts/fa/fa-solid-900.ttf') }}") format("truetype");
            }

            @font-face {
                font-family: "Font Awesome 7 Brands";
                font-weight: 400;
                font-style: normal;
                src: url("file://{{ public_path('fonts/fa/fa-brands-400.ttf') }}") format("truetype");
            }
        </style>
    @else
        <link rel="stylesheet" href="{{ Vite::asset('resources/css/fontawesome.css') }}">
    @endif
    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            background: #fff;
            line-height: 1.45;
        }

        .page {
            padding: 18mm 16mm;
        }

        /* Header */
        .header-name {
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 0.5pt;
        }

        .header-role {
            font-size: 11pt;
            font-weight: normal;
            letter-spacing: 1pt;
            text-transform: uppercase;
            color: #444;
            margin-top: 2pt;
        }

        .contact-line {
            margin-top: 6pt;
            font-size: 9pt;
            color: #333;
        }

        /* Sections */
        .section {
            margin-top: 14pt;
        }

        .section-title {
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8pt;
            border-bottom: 1pt solid #000;
            padding-bottom: 2pt;
            margin-bottom: 8pt;
        }

        /* Two-column row for entries */
        .entry-header {
            display: table;
            width: 100%;
        }

        .entry-title-col {
            display: table-cell;
            font-weight: bold;
            font-size: 10pt;
        }

        .entry-date-col {
            display: table-cell;
            text-align: right;
            font-size: 9.5pt;
            white-space: nowrap;
            width: 130pt;
        }

        .entry-sub {
            font-size: 9pt;
            color: #444;
            margin-top: 1pt;
        }

        .entry-bullets {
            margin-top: 4pt;
            padding-left: 14pt;
        }

        .entry-bullets li {
            margin-bottom: 2pt;
            font-size: 9.5pt;
        }

        .entry {
            margin-bottom: 9pt;
        }

        /* Skills grid */
        .skills-table {
            width: 100%;
            border-collapse: collapse;
        }

        .skills-table td {
            padding: 2pt 4pt 2pt 0;
            font-size: 9.5pt;
            vertical-align: top;
        }

        .skills-table td.skill-name {
            font-weight: bold;
            width: 130pt;
            white-space: nowrap;
        }

        /* Projects */
        .project-links {
            font-size: 8.5pt;
            color: #444;
            margin-top: 1pt;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header-name">{{ $name }}</div>
    <div class="header-role">{{ $role }}</div>

    @if ($contactItems->isNotEmpty())
        @php
            $faIconMap = [
                'fa-solid fa-envelope'        => ["\u{f0e0}", 'Font Awesome 7 Free',   900],
                'fa-solid fa-phone'           => ["\u{f095}", 'Font Awesome 7 Free',   900],
                'fa-solid fa-location-dot'    => ["\u{f3c5}", 'Font Awesome 7 Free',   900],
                'fa-solid fa-globe'           => ["\u{f0ac}", 'Font Awesome 7 Free',   900],
                'fa-solid fa-link'            => ["\u{f0c1}", 'Font Awesome 7 Free',   900],
                'fa-brands fa-linkedin'       => ["\u{f08c}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-github'         => ["\u{f09b}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-x-twitter'      => ["\u{e61b}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-twitter'        => ["\u{f099}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-instagram'      => ["\u{f16d}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-youtube'        => ["\u{f167}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-facebook'       => ["\u{f09a}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-discord'        => ["\u{f392}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-telegram'       => ["\u{f2c6}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-whatsapp'       => ["\u{f232}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-medium'         => ["\u{f23a}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-stack-overflow' => ["\u{f16c}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-behance'        => ["\u{f1b4}", 'Font Awesome 7 Brands', 400],
                'fa-brands fa-dribbble'       => ["\u{f17d}", 'Font Awesome 7 Brands', 400],
            ];
            $contactRows = $contactItems->chunk(3);
        @endphp
        <div class="contact-line">
            <table style="width:100%; border-collapse:collapse;">
                @foreach ($contactRows as $row)
                    <tr>
                        @foreach ($row as $item)
                            @php
                                [$iconChar, $iconFamily, $iconWeight] = $faIconMap[$item->icon] ?? [null, null, null];
                            @endphp
                            <td style="padding:1pt 6pt 1pt 0; white-space:nowrap;">
                                @if ($item->url)
                                    <a href="{{ $item->url }}" style="color:#333; text-decoration:none;">
                                        @if ($forPdf ?? false)
                                            @if ($iconChar)
                                                <span style="font-family:'{{ $iconFamily }}'; font-weight:{{ $iconWeight }}; font-style:normal; font-size:11pt;">{{ $iconChar }}</span>&nbsp;
                                            @endif
                                        @elseif ($item->icon)
                                            <i class="{{ $item->icon }}" style="font-size:11pt; margin-right:3px;"></i>
                                        @endif
                                        {{ $item->label }}
                                    </a>
                                @else
                                    @if ($forPdf ?? false)
                                        @if ($iconChar)
                                            <span style="font-family:'{{ $iconFamily }}'; font-weight:{{ $iconWeight }}; font-style:normal; font-size:11pt;">{{ $iconChar }}</span>&nbsp;
                                        @endif
                                    @elseif ($item->icon)
                                        <i class="{{ $item->icon }}" style="font-size:11pt; margin-right:3px;"></i>
                                    @endif
                                    {{ $item->label }}
                                @endif
                            </td>
                        @endforeach
                        @for ($i = $row->count(); $i < 3; $i++)
                            <td></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    {{-- Objective --}}
    @if ($about)
        <div class="section">
            <div class="section-title">Objective</div>
            <p style="font-size:9.5pt;">{{ $about }}</p>
        </div>
    @endif

    {{-- Experience --}}
    @if ($experiences->isNotEmpty())
        <div class="section">
            <div class="section-title">Experience</div>
            @foreach ($experiences as $experience)
                <div class="entry">
                    <div class="entry-header">
                        <div class="entry-title-col">{{ $experience->title }} | {{ $experience->company }}</div>
                        <div class="entry-date-col">{{ $experience->period }}</div>
                    </div>
                    @if (!empty($experience->bullets))
                        <ul class="entry-bullets">
                            @foreach ($experience->bullets as $bullet)
                                <li>{{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Education --}}
    @if ($educations->isNotEmpty())
        <div class="section">
            <div class="section-title">Education</div>
            @foreach ($educations as $education)
                <div class="entry">
                    <div class="entry-header">
                        <div class="entry-title-col">{{ $education->title }} | {{ $education->institution }}</div>
                        <div class="entry-date-col">{{ $education->period }}</div>
                    </div>
                    @if ($education->description)
                        <div class="entry-sub">{{ $education->description }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Skills --}}
    @if ($skillCategories->isNotEmpty())
        <div class="section">
            <div class="section-title">Skills</div>
            <table class="skills-table">
                @foreach ($skillCategories as $category)
                    @if (!empty($category->items))
                        <tr>
                            <td class="skill-name">{{ $category->name }}</td>
                            <td>{{ implode(', ', $category->items) }}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>
    @endif

    {{-- Projects --}}
    @if ($projects->isNotEmpty())
        <div class="section">
            <div class="section-title">Projects</div>
            @foreach ($projects as $project)
                <div class="entry">
                    <div class="entry-header">
                        <div class="entry-title-col">{{ $project->name }}</div>
                        @if (!empty($project->tech))
                            <div class="entry-date-col" style="font-weight:normal;font-size:9pt;">{{ implode(', ', $project->tech) }}</div>
                        @endif
                    </div>
                    @if ($project->subtitle)
                        <div class="entry-sub">{{ $project->subtitle }}</div>
                    @endif
                    @if (!empty($project->links))
                        <div class="project-links">
                            @foreach ($project->links as $link)
                                {{ $link['label'] }}{{ !$loop->last ? '  |  ' : '' }}
                            @endforeach
                        </div>
                    @endif
                    @if (!empty($project->bullets))
                        <ul class="entry-bullets">
                            @foreach ($project->bullets as $bullet)
                                <li>{{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</div>
</body>
</html>
