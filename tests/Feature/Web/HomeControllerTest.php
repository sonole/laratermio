<?php

use App\Enums\NavItemType;
use App\Enums\SettingKey;
use App\Livewire\Terminal;
use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\NavItem;
use App\Models\Project;
use App\Models\SkillCategory;
use App\Models\TerminalCommand;
use App\Services\CvService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/** @return array<string, mixed> */
function webHomeJsonLd(string $html): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    return json_decode($matches[1] ?? '{}', true) ?? [];
}

beforeEach(function () {
    $this->webHomeDisk = storage_path('framework/testing/disks/web-home');
    config(['filesystems.disks.public.root' => $this->webHomeDisk, 'app.url' => 'https://portfolio.test']);
    Storage::forgetDisk('public');
});

afterEach(function () {
    File::deleteDirectory($this->webHomeDisk);
});

describe('home page', function () {
    it('renders the terminal page', function () {
        $this->get('/')
            ->assertOk()
            ->assertSeeLivewire(Terminal::class);
    });

    it('works on a completely empty database using default identity', function () {
        $this->get('/')
            ->assertOk()
            ->assertSee('Dev McDevface — Developer', false);
    });

    it('tells visitors about fastfetch only when that command is active', function () {
        seedSystem();

        $this->get('/')->assertSee('run `fastfetch` in the terminal', false);

        TerminalCommand::query()->where('name', 'fastfetch')->update(['is_active' => false]);

        $this->get('/')->assertDontSee('run `fastfetch` in the terminal', false);
    });
});

describe('SEO tags', function () {
    it('uses the configured SEO title, description, canonical url and site name', function () {
        cvSetting(SettingKey::Name, 'Alex Example');
        cvSetting(SettingKey::SeoTitle, 'Alex Example | Portfolio');
        cvSetting(SettingKey::SeoDescription, 'Laravel developer from Athens.');

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Alex Example | Portfolio</title>', false)
            ->assertSee('<meta name="description" content="Laravel developer from Athens.">', false)
            ->assertSee('<link rel="canonical" href="https://portfolio.test">', false)
            ->assertSee('<meta property="og:title" content="Alex Example | Portfolio">', false)
            ->assertSee('<meta property="og:description" content="Laravel developer from Athens.">', false)
            ->assertSee('<meta property="og:site_name" content="Alex Example">', false)
            ->assertSee('<meta name="twitter:title" content="Alex Example | Portfolio">', false);
    });

    it('falls back to the app name for the title and an empty description', function () {
        config(['app.name' => 'Fallback App']);

        $this->get('/')
            ->assertSee('<title>Fallback App</title>', false)
            ->assertSee('<meta name="description" content="">', false);
    });

    it('strips a trailing slash from the canonical url', function () {
        config(['app.url' => 'https://portfolio.test/']);

        $this->get('/')->assertSee('<link rel="canonical" href="https://portfolio.test">', false);
    });

    it('escapes admin supplied text in meta tags', function () {
        cvSetting(SettingKey::SeoDescription, 'Say "hi" <b>there</b>');

        $this->get('/')
            ->assertSee('<meta name="description" content="Say &quot;hi&quot; &lt;b&gt;there&lt;/b&gt;">', false);
    });

    it('uses a small twitter card and no image tags when no social image is set', function () {
        $this->get('/')
            ->assertSee('<meta name="twitter:card" content="summary">', false)
            ->assertDontSee('og:image', false)
            ->assertDontSee('twitter:image', false)
            ->assertDontSee('twitter:site', false);
    });

    it('adds an absolute social image and a large twitter card when one is uploaded', function () {
        cvSetting(SettingKey::SeoOgImage, 'uploads/og.png');

        $this->get('/')
            ->assertSee('<meta property="og:image" content="https://portfolio.test/storage/uploads/og.png">', false)
            ->assertSee('<meta name="twitter:image" content="https://portfolio.test/storage/uploads/og.png">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    });

    it('adds the twitter handle when one is set', function () {
        cvSetting(SettingKey::SeoTwitterHandle, '@alexample');

        $this->get('/')->assertSee('<meta name="twitter:site" content="@alexample">', false);
    });

    it('serves the default favicon', function () {
        $this->get('/')->assertSee('<link rel="shortcut icon" href="/favicon.ico"', false);
    });

    it('serves the uploaded favicon from storage', function () {
        cvSetting(SettingKey::Favicon, 'uploads/fav.png');

        $this->get('/')->assertSee('<link rel="shortcut icon" href="/storage/uploads/fav.png"', false);
    });

    it('describes the person in JSON-LD', function () {
        cvSetting(SettingKey::Name, 'Alex Example');
        cvSetting(SettingKey::Role, 'Software Engineer');
        cvSetting(SettingKey::SeoDescription, 'Laravel developer.');
        ContactItem::factory()->create(['label' => 'GitHub', 'url' => 'https://github.com/alex', 'sort_order' => 1]);
        ContactItem::factory()->create(['label' => 'Mail', 'url' => 'mailto:alex@example.com', 'sort_order' => 2]);
        ContactItem::factory()->create(['label' => 'Old', 'url' => 'https://old.example.com', 'is_active' => false]);

        $jsonLd = webHomeJsonLd($this->get('/')->getContent());

        expect($jsonLd)->toMatchArray([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => 'Alex Example',
            'jobTitle' => 'Software Engineer',
            'description' => 'Laravel developer.',
            'url' => 'https://portfolio.test',
        ])->and($jsonLd['sameAs'])->toBe(['https://github.com/alex']);
    });

    it('leaves empty values out of the JSON-LD', function () {
        $jsonLd = webHomeJsonLd($this->get('/')->getContent());

        expect(array_keys($jsonLd))->toBe(['@context', '@type', 'name', 'jobTitle', 'url']);
    });

    it('does not let a description close the JSON-LD script tag', function () {
        cvSetting(SettingKey::SeoDescription, '</script><script>alert(1)</script>');

        $this->get('/')->assertDontSee('</script><script>alert(1)', false);
    });
});

describe('crawler content', function () {
    it('lists the active content in the hidden portfolio section', function () {
        cvSetting(SettingKey::Name, 'Alex Example');
        cvSetting(SettingKey::Role, 'Software Engineer');
        cvSetting(SettingKey::About, 'I build things for the web.');
        Experience::factory()->create(['title' => 'Staff Engineer', 'company' => 'Acme Inc', 'bullets' => ['Led the platform team']]);
        Education::factory()->create(['title' => 'BSc Informatics', 'institution' => 'Athens Uni']);
        Education::factory()->certification()->create(['title' => 'AWS Architect', 'institution' => 'Amazon', 'certificate_url' => 'https://aws.example.com/cert']);
        Project::factory()->create(['name' => 'Laratermio', 'subtitle' => 'Terminal portfolio', 'tech' => ['PHP', 'Livewire'], 'links' => [['label' => 'Source', 'url' => 'https://github.com/alex/laratermio']]]);
        SkillCategory::factory()->create(['name' => 'Backend', 'items' => ['PHP', 'MySQL']]);
        ContactItem::factory()->create(['label' => 'GitHub', 'url' => 'https://github.com/alex']);
        ContactItem::factory()->create(['label' => 'Based in Athens', 'url' => null]);

        $this->get('/')
            ->assertSee('<h1>Alex Example — Software Engineer</h1>', false)
            ->assertSee('I build things for the web.')
            ->assertSee('Staff Engineer at Acme Inc')
            ->assertSee('Led the platform team')
            ->assertSee('BSc Informatics — Athens Uni')
            ->assertSee('Degree / Programme')
            ->assertSee('AWS Architect — Amazon')
            ->assertSee('Certification')
            ->assertSee('<a href="https://aws.example.com/cert">View certificate</a>', false)
            ->assertSee('<h3>Laratermio</h3>', false)
            ->assertSee('Technologies: PHP, Livewire')
            ->assertSee('<a href="https://github.com/alex/laratermio">Source</a>', false)
            ->assertSee('PHP, MySQL')
            ->assertSee('<a href="https://github.com/alex">GitHub</a>', false)
            ->assertSee('<li>Based in Athens</li>', false);
    });

    it('omits sections that have no content', function () {
        $this->get('/')
            ->assertDontSee('<h2>Experience</h2>', false)
            ->assertDontSee('<h2>Education</h2>', false)
            ->assertDontSee('<h2>Projects</h2>', false)
            ->assertDontSee('<h2>Skills</h2>', false)
            ->assertDontSee('<h2>Contact</h2>', false);
    });

    it('hides inactive content and keeps the configured order', function () {
        Experience::factory()->create(['title' => 'Second Role', 'sort_order' => 2]);
        Experience::factory()->create(['title' => 'First Role', 'sort_order' => 1]);
        Experience::factory()->inactive()->create(['title' => 'Hidden Role']);
        Project::factory()->inactive()->create(['name' => 'Hidden Project']);
        SkillCategory::factory()->inactive()->create(['name' => 'Hidden Skills']);
        Education::factory()->inactive()->create(['title' => 'Hidden Degree']);
        ContactItem::factory()->inactive()->create(['label' => 'Hidden Contact']);

        $this->get('/')
            ->assertSeeInOrder(['First Role', 'Second Role'])
            ->assertDontSee('Hidden Role')
            ->assertDontSee('Hidden Project')
            ->assertDontSee('Hidden Skills')
            ->assertDontSee('Hidden Degree')
            ->assertDontSee('Hidden Contact');
    });
});

describe('navigation bar', function () {
    function webHomeCommandNavItem(string $label, int $sort, bool $active = true): NavItem
    {
        $command = TerminalCommand::factory()->create(['display_label' => $label]);

        return NavItem::factory()->create([
            'type' => NavItemType::Command,
            'terminal_command_id' => $command->id,
            'label' => null,
            'url' => null,
            'sort_order' => $sort,
            'is_active' => $active,
        ]);
    }

    it('renders the seeded navigation', function () {
        seedSystem();

        $this->get('/')
            ->assertSee('terminal-btn', false)
            ->assertSee('window.termNavExec(\'experience -a\')', false)
            ->assertSee('window.termNavExec(\'projects -a\')', false)
            ->assertSee('window.termNavExec(\'about\')', false);
    });

    it('lists command buttons in sort order and hides inactive ones', function () {
        webHomeCommandNavItem('Zulu Button', 2);
        webHomeCommandNavItem('Alpha Button', 1);
        webHomeCommandNavItem('Hidden Button', 0, active: false);

        $this->get('/')
            ->assertSeeInOrder(['Alpha Button', 'Zulu Button'])
            ->assertDontSee('Hidden Button');
    });

    it('lists link buttons that point at their url and hides inactive ones', function () {
        NavItem::factory()->create(['label' => 'My Blog', 'url' => 'https://blog.example.com', 'target' => '_blank', 'sort_order' => 2]);
        NavItem::factory()->create(['label' => 'Early Link', 'url' => 'https://early.example.com', 'target' => '_self', 'sort_order' => 1]);
        NavItem::factory()->inactive()->create(['label' => 'Gone Link', 'url' => 'https://gone.example.com']);

        $this->get('/')
            ->assertSee('href="https://blog.example.com"', false)
            ->assertSee('target="_self"', false)
            ->assertSeeInOrder(['Early Link', 'My Blog'])
            ->assertDontSee('Gone Link')
            ->assertDontSee('https://gone.example.com');
    });

    it('hides the CV button until a CV has been generated', function () {
        NavItem::factory()->create(['type' => NavItemType::Cv, 'label' => 'Grab my CV', 'url' => null]);

        $this->get('/')->assertDontSee('Grab my CV');

        Storage::disk('public')->put(CvService::PATH, 'pdf');

        $this->get('/')
            ->assertSee('Grab my CV')
            ->assertSee('href="'.route('cv').'"', false);
    });

    it('hides an inactive CV button even when a CV exists', function () {
        NavItem::factory()->inactive()->create(['type' => NavItemType::Cv, 'label' => 'Grab my CV', 'url' => null]);
        Storage::disk('public')->put(CvService::PATH, 'pdf');

        $this->get('/')->assertDontSee('Grab my CV');
    });
});
