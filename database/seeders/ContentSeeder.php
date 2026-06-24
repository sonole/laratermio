<?php

namespace Database\Seeders;

use App\Enums\SettingKey;
use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SkillCategory;
use App\Services\CvService;
use App\Services\UploadService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContentSeeder extends Seeder
{
    public function __construct(
        protected UploadService $upload,
        protected CvService $cvService,
    ) {}

    public function run(): void
    {
        if (config('app.seed_content.settings', true)) {
            $this->seedSettings();
        }
        if (config('app.seed_content.educations', true)) {
            $this->seedEducations();
        }
        if (config('app.seed_content.experiences', true)) {
            $this->seedExperiences();
        }
        if (config('app.seed_content.projects', true)) {
            $this->seedProjects();
        }
        if (config('app.seed_content.skills', true)) {
            $this->seedSkillCategories();
        }
        if (config('app.seed_content.contact', true)) {
            $this->seedContactItems();
        }
        if (config('app.seed_content.cv', true)) {
            $this->generateCv();
        }
    }

    protected function seedSettings(): void
    {
        Storage::disk('public')->deleteDirectory(Setting::UPLOAD_DIRECTORY);

        foreach (data_get($this->config(), 'settings', []) as $key => $value) {
            if (is_array($value) && isset($value['stub'])) {
                $value = $this->upload->copyStubToStorage($value['stub'], Setting::UPLOAD_DIRECTORY);
            }
            Setting::query()->where('key', $key)->update(['value' => $value]);
        }
    }

    protected function seedEducations(): void
    {
        Education::query()->truncate();

        foreach (data_get($this->config(), 'educations', []) as $index => $data) {
            Education::query()->create([
                'title' => $data['title'],
                'institution' => $data['institution'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_certification' => $data['is_certification'],
                'description' => $data['description'] ?? null,
                'certificate_url' => $data['certificate_url'] ?? null,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }

    protected function seedExperiences(): void
    {
        Experience::query()->truncate();

        foreach (data_get($this->config(), 'experiences') as $index => $data) {
            Experience::query()->create([
                'title' => $data['title'],
                'company' => $data['company'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_current' => $data['current'],
                'bullets' => $data['bullets'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }

    protected function seedProjects(): void
    {
        Media::query()->where('model_type', (new Project)->getMorphClass())->get()->each->delete();
        Storage::disk('public')->deleteDirectory('uploads/projects');
        Project::query()->truncate();

        foreach (data_get($this->config(), 'projects', []) as $index => $data) {
            $project = Project::query()->create([
                'name' => $data['name'],
                'subtitle' => $data['subtitle'],
                'tech' => $data['tech'],
                'bullets' => $data['bullets'],
                'links' => $data['links'],
                'sort_order' => $index,
                'is_active' => true,
            ]);

            $stubDir = $data['stub_dir'] ?? null;

            if ($stubDir) {
                $mainImageFile = public_path('stubs/'.$stubDir.'/main.webp');
                if (file_exists($mainImageFile)) {
                    $project->addMedia($mainImageFile)
                        ->preservingOriginal()
                        ->toMediaCollection('main_image');
                }

                foreach ($data['gallery_stubs'] ?? [] as $galleryStub) {
                    $galleryFile = public_path('stubs/'.$stubDir.'/'.$galleryStub);
                    if (file_exists($galleryFile)) {
                        $project->addMedia($galleryFile)
                            ->preservingOriginal()
                            ->toMediaCollection('gallery');
                    }
                }

                if (! empty($data['video_stub'])) {
                    $videoFile = public_path('stubs/'.$stubDir.'/'.$data['video_stub']);
                    if (file_exists($videoFile)) {
                        $project->addMedia($videoFile)
                            ->preservingOriginal()
                            ->toMediaCollection('video_file');
                    }
                }
            }

            if (! empty($data['video_url'])) {
                $project->update(['video_url' => $data['video_url']]);
            }
        }
    }

    protected function seedSkillCategories(): void
    {
        SkillCategory::query()->truncate();

        foreach (data_get($this->config(), 'skills', []) as $index => $data) {
            SkillCategory::query()->create([
                'name' => $data['category'],
                'items' => $data['items'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }

    protected function generateCv(): void
    {
        $this->cvService->generate();
    }

    protected function seedContactItems(): void
    {
        ContactItem::query()->truncate();

        foreach (data_get($this->config(), 'contact_items', []) as $index => $item) {
            ContactItem::query()->create([
                'icon' => $item['icon'],
                'label' => $item['label'],
                'url' => $item['url'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }

    /** @return array<string, mixed> */
    protected function config(): array
    {
        return [
            'settings' => [
                SettingKey::Name->value => 'Dev McDevface',
                SettingKey::Role->value => 'Professional Coffee-to-Code Converter',
                SettingKey::About->value => 'By day I write Laravel. By night I also write Laravel. I believe every problem can be solved with a service class, a facade, and enough caffeine. My code is self-documenting — it just documents itself as "undocumented". Replace this with your actual bio through the admin panel.',
                SettingKey::AsciiArtEnabled->value => '1',
                SettingKey::AsciiArt->value => ['stub' => 'laratermio/settings/ascii-art.txt'],
                SettingKey::AsciiArtSize->value => '0.70',
                SettingKey::AsciiArtColor->value => '#4ade80',
                SettingKey::PromptUsername->value => 'visitor',
                SettingKey::PromptUsernameColor->value => '#4ade80',
                SettingKey::PromptHostname->value => 'laratermio',
                SettingKey::PromptHostnameColor->value => '#60a5fa',
                SettingKey::PromptSeparatorColor->value => '#6b7280',
                SettingKey::SeoTitle->value => 'Dev McDevface',
                SettingKey::SeoDescription->value => 'Professional Coffee-to-Code Converter. 10x developer (based on self-assessment). Currently hiring myself.',
                SettingKey::Favicon->value => null,
                SettingKey::SeoOgImage->value => ['stub' => 'laratermio/settings/og-image.png'],
                SettingKey::SeoTwitterHandle->value => null,
            ],
            'experiences' => [
                [
                    'title' => 'Senior Bug Creator',
                    'company' => 'Legacy Systems Inc.',
                    'start_date' => '2022-03-01',
                    'end_date' => null,
                    'current' => true,
                    'bullets' => [
                        'Responsible for introducing 3–5 subtle bugs per sprint, keeping the QA team employed and motivated.',
                        'Pioneered the "works on my machine" deployment strategy, now adopted company-wide.',
                        'Maintained a 47,000-line God class that nobody dares touch — successfully argued it is a "monorepo".',
                        'Awarded Employee of the Month after fixing a bug I had introduced the previous month.',
                    ],
                ],
                [
                    'title' => 'Full Stack Googler',
                    'company' => 'Stack Overflow Consultancy',
                    'start_date' => '2019-01-01',
                    'end_date' => '2022-02-01',
                    'current' => false,
                    'bullets' => [
                        'Expertly copy-pasted solutions from Stack Overflow, occasionally reading past the first answer.',
                        'Reduced time-to-feature by 40% by removing all unit tests — nothing failed after that.',
                        'Delivered a real-time dashboard that updates every 24 hours; client described it as "real enough".',
                    ],
                ],
                [
                    'title' => 'Junior Everything',
                    'company' => 'My Cousin\'s Startup',
                    'start_date' => '2017-06-01',
                    'end_date' => '2018-12-01',
                    'current' => false,
                    'bullets' => [
                        'Served as developer, designer, sysadmin, product manager, and "the one who fixes the printer".',
                        'Launched an MVP in two weeks; it was mostly a landing page with a countdown timer.',
                        'The startup pivoted four times. I pivoted with it. We are now a dog grooming platform.',
                    ],
                ],
            ],
            'educations' => [
                [
                    'title' => "Bachelor's Degree: Computer Science",
                    'institution' => 'University of Somewhere',
                    'start_date' => '2013-09-01',
                    'end_date' => '2017-06-01',
                    'is_certification' => false,
                    'description' => "Thesis: \"Why Tabs Are Objectively Better Than Spaces: A 94-Page Defense\"\nGPA: high enough to graduate, low enough to stay humble",
                    'certificate_url' => null,
                ],
                [
                    'title' => 'Certified Cloud Practitioner',
                    'institution' => 'AWS',
                    'start_date' => '2021-05-01',
                    'end_date' => null,
                    'is_certification' => true,
                    'description' => null,
                    'certificate_url' => null,
                ],
                [
                    'title' => 'The Complete JavaScript Course (watched at 2×)',
                    'institution' => 'Udemy',
                    'start_date' => '2020-03-01',
                    'end_date' => null,
                    'is_certification' => true,
                    'description' => null,
                    'certificate_url' => null,
                ],
            ],
            'projects' => [
                [
                    'name' => 'Todo App (It\'s Different This Time)',
                    'subtitle' => 'The todo app to end all todo apps',
                    'stub_dir' => 'laratermio/projects/todo-app',
                    'video_stub' => null,
                    'gallery_stubs' => [],
                    'video_url' => null,
                    'tech' => ['Laravel', 'Livewire', 'Tailwind CSS', 'MySQL', 'Existential Dread'],
                    'links' => [
                        ['label' => 'GitHub', 'url' => 'https://github.com/your-username/todo-app-v12'],
                    ],
                    'bullets' => [
                        'Built yet another todo app because the previous 11 versions lacked proper architecture.',
                        'Implemented a microservices approach for a CRUD app used by one person (me).',
                        'Features real-time updates, offline support, and a dark mode nobody asked for.',
                        'Zero open issues — all issues were closed as "works as intended".',
                    ],
                ],
                [
                    'name' => 'Is It a Hotdog?',
                    'subtitle' => 'AI-powered food classification at its finest',
                    'stub_dir' => 'laratermio/projects/hotdog-classifier',
                    'video_stub' => null,
                    'gallery_stubs' => [],
                    'video_url' => null,
                    'tech' => ['Python', 'TensorFlow', 'Flask', 'Regret'],
                    'links' => [
                        ['label' => 'GitHub', 'url' => 'https://github.com/your-username/hotdog-classifier'],
                    ],
                    'bullets' => [
                        'Trained a neural network to answer the most important question of our time: hotdog or not hotdog.',
                        'Achieved 94% accuracy on hotdogs; 12% accuracy on everything else.',
                        'Went viral on Twitter for 48 hours; now used by exactly nobody in production.',
                        'Dataset sourced entirely from Google Images — legally ambiguous but spiritually correct.',
                    ],
                ],
                [
                    'name' => 'My Portfolio (v42)',
                    'subtitle' => 'Rebuilt from scratch. Again.',
                    'stub_dir' => 'laratermio/projects/portfolio-v42',
                    'video_stub' => null,
                    'gallery_stubs' => [],
                    'video_url' => null,
                    'tech' => ['Laravel', 'Livewire', 'Tailwind CSS', 'laratermio', 'Self-Doubt'],
                    'links' => [
                        ['label' => 'GitHub', 'url' => 'https://github.com/your-username/portfolio'],
                        ['label' => 'Live', 'url' => 'https://yourname.dev'],
                    ],
                    'bullets' => [
                        'Spent 3 weeks perfecting the design and 2 days adding actual content.',
                        'Switched frameworks 6 times before discovering laratermio and calling it done.',
                        'The terminal interface was described by my mum as "very computery".',
                        'Currently the most-visited website in my browser history (I keep checking it).',
                    ],
                ],
            ],
            'skills' => [
                [
                    'category' => 'Definitely Know',
                    'items' => ['Laravel', 'PHP', 'MySQL', 'Tailwind CSS', 'Livewire', 'Git'],
                ],
                [
                    'category' => 'Listed on CV',
                    'items' => ['Docker', 'Redis', 'Vue.js', 'React', 'AWS', 'Kubernetes'],
                ],
                [
                    'category' => 'Googled Once',
                    'items' => ['Rust', 'Haskell', 'Assembly', 'Regex (any regex)'],
                ],
                [
                    'category' => 'Soft Skills',
                    'items' => ['Confidence in stand-ups', 'Explaining "it\'s complicated"', 'Nodding during architecture discussions'],
                ],
            ],
            'contact_items' => [
                [
                    'icon' => 'fa-solid fa-envelope',
                    'label' => 'dev@example.com',
                    'url' => 'mailto:dev@example.com',
                ],
                [
                    'icon' => 'fa-solid fa-location-dot',
                    'label' => 'The Cloud (us-east-1)',
                    'url' => null,
                ],
                [
                    'icon' => 'fa-brands fa-github',
                    'label' => 'github.com/your-username',
                    'url' => 'https://github.com/your-username',
                ],
                [
                    'icon' => 'fa-brands fa-linkedin',
                    'label' => 'linkedin.com/in/your-profile',
                    'url' => 'https://linkedin.com/in/your-profile',
                ],
            ],
        ];
    }
}
