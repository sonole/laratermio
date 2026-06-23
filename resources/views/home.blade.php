<!--
██╗  ░██████╗███████╗███████╗  ██╗░░░██╗░█████╗░██╗░░░██╗
██║  ██╔════╝██╔════╝██╔════╝  ╚██╗░██╔╝██╔══██╗██║░░░██║
██║  ╚█████╗░█████╗░░█████╗░░  ░╚████╔╝░██║░░██║██║░░░██║
██║  ░╚═══██╗██╔══╝░░██╔══╝░░  ░░╚██╔╝░░██║░░██║██║░░░██║
██║  ██████╔╝███████╗███████╗  ░░░██║░░░╚█████╔╝╚██████╔╝
╚═╝  ╚═════╝░╚══════╝╚══════╝  ░░░╚═╝░░░░╚════╝░░╚═════╝░
{{ $hasFastfetch ? "\n  curious? run `fastfetch` in the terminal." : '' }}
-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['desc'] }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['desc'] }}">
    <meta property="og:site_name" content="{{ $seo['name'] }}">
    @if($seo['og_image'])
    <meta property="og:image" content="{{ $seo['og_image'] }}">
    @endif

    {{-- Twitter / X Card --}}
    <meta name="twitter:card" content="{{ $seo['og_image'] ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['desc'] }}">
    @if($seo['og_image'])
    <meta name="twitter:image" content="{{ $seo['og_image'] }}">
    @endif
    @if($seo['twitter_handle'])
    <meta name="twitter:site" content="{{ $seo['twitter_handle'] }}">
    @endif

    {{-- JSON-LD Person schema --}}
    <script type="application/ld+json">{!! json_encode($seo['json_ld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>

    <link rel="shortcut icon" href="{{ $faviconUrl }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            let t = localStorage.getItem('terminal-theme');
            if (!t || t === 'system') {
                t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            }
            if (t === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    @livewireStyles
</head>
<body>
    <livewire:terminal />

    {{-- Visually hidden content for search engine crawlers. --}}
    <section aria-label="Portfolio" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">
        <h1>{{ $seo['name'] }} — {{ $seo['role'] }}</h1>
        @if($seo['desc'])<p>{{ $seo['desc'] }}</p>@endif
        @if($about)<p>{{ $about }}</p>@endif

        @if($experiences->isNotEmpty())
        <h2>Experience</h2>
        @foreach($experiences as $exp)
        <article>
            <h3>{{ $exp->title }} at {{ $exp->company }}</h3>
            <p>{{ $exp->period }}</p>
            @foreach($exp->bullets ?? [] as $bullet)<p>{{ $bullet }}</p>@endforeach
        </article>
        @endforeach
        @endif

        @if($educations->isNotEmpty())
        <h2>Education</h2>
        @foreach($educations as $edu)
        <article>
            <h3>{{ $edu->title }} — {{ $edu->institution }}</h3>
            <p>{{ $edu->is_certification ? 'Certification' : 'Degree / Programme' }} · {{ $edu->period }}</p>
            @if($edu->description)<p>{{ $edu->description }}</p>@endif
            @if($edu->certificate_url)<p><a href="{{ $edu->certificate_url }}">View certificate</a></p>@endif
        </article>
        @endforeach
        @endif

        @if($projects->isNotEmpty())
        <h2>Projects</h2>
        @foreach($projects as $project)
        <article>
            <h3>{{ $project->name }}</h3>
            @if($project->subtitle)<p>{{ $project->subtitle }}</p>@endif
            @if($project->tech)<p>Technologies: {{ implode(', ', $project->tech) }}</p>@endif
            @foreach($project->bullets ?? [] as $bullet)<p>{{ $bullet }}</p>@endforeach
            @if($project->links)
            <ul>
                @foreach($project->links as $link)
                <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            @endif
        </article>
        @endforeach
        @endif

        @if($skillCategories->isNotEmpty())
        <h2>Skills</h2>
        @foreach($skillCategories as $category)
        <div>
            <h3>{{ $category->name }}</h3>
            @if($category->items)<p>{{ implode(', ', $category->items) }}</p>@endif
        </div>
        @endforeach
        @endif

        @if($contacts->isNotEmpty())
        <h2>Contact</h2>
        <ul>
        @foreach($contacts as $contact)
            @if($contact->url)
            <li><a href="{{ $contact->url }}">{{ $contact->label }}</a></li>
            @else
            <li>{{ $contact->label }}</li>
            @endif
        @endforeach
        </ul>
        @endif
    </section>

    @livewireScripts
</body>
</html>