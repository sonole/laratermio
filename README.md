```
██╗      █████╗ ██████╗  █████╗ ████████╗███████╗██████╗ ███╗   ███╗██╗ ██████╗
██║     ██╔══██╗██╔══██╗██╔══██╗╚══██╔══╝██╔════╝██╔══██╗████╗ ████║██║██╔═══██╗
██║     ███████║██████╔╝███████║   ██║   █████╗  ██████╔╝██╔████╔██║██║██║   ██║
██║     ██╔══██║██╔══██╗██╔══██║   ██║   ██╔══╝  ██╔══██╗██║╚██╔╝██║██║██║   ██║
███████╗██║  ██║██║  ██║██║  ██║   ██║   ███████╗██║  ██║██║ ╚═╝ ██║██║╚██████╔╝
╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝   ╚═╝   ╚══════╝╚═╝  ╚═╝╚═╝     ╚═╝╚═╝ ╚═════╝
```

![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white)
![Livewire](https://img.shields.io/badge/livewire-%234e56a6.svg?style=for-the-badge&logo=livewire&logoColor=white)
![Filament](https://img.shields.io/badge/filament-%23FDAE4B.svg?style=for-the-badge&logo=filament&logoColor=black&logoSize=auto)
![TailwindCSS](https://img.shields.io/badge/tailwindcss-%2338B2AC.svg?style=for-the-badge&logo=tailwind-css&logoColor=white)

![Demo](public/demo.gif)

**Your portfolio lives in the terminal.** Visitors type commands to explore your work — no scrolling, no nav menus, no template layout. Content is managed through a Filament admin panel, and the whole thing generates a print-ready PDF CV on demand.

> **Live app:** <a href="https://apaliampelos.me" target="_blank">apaliampelos.me</a>  
> **Docs:** <a href="https://apaliampelos.me/docs" target="_blank">apaliampelos.me/docs</a>

---

## How it works

The entire portfolio runs inside a browser-based terminal emulator. A visitor arrives, sees a prompt, and types commands to explore.

```
visitor@laratermio:~$ help

  explore
    about                       — Who I am and what drives me
    contact                     — Get in touch
    contact <email> <message>   — Drop your email and a short message
    education                   — Education & certifications
    open <name>                 — Open a link in a new tab
    search <query>              — Search across education, experience, projects, skills
    skills                      — Technical skills and stack

  experience
    experience                  — Work history
    experience <n>              — Jump directly to experience n
    experience -a               — Full work history at once

  projects
    projects                    — Side projects and open source
    projects <n>                — Jump directly to project n
    projects -a                 — All projects at once
    
  system
    cd <dir>                    — Navigate the portfolio filesystem
    clear                       — Clear the terminal screen
    history                     — Show command history
    ls                          — List files in the current directory
    theme <mode>                — Switch color scheme (light / dark / system)
    whoami                      — Print current user identity
```

> Curious? There are more commands that won't appear in `help` — explore and find them.

### Admin panel

Everything editable at `/admin` via Filament — no code changes needed:

| Area | What you can manage |
|---|---|
| Content | Experience, education, projects, skills, contact info |
| Terminal | Enable/disable commands, edit labels and descriptions |
| Navigation | Which commands appear in the nav bar and in what order |
| Settings | Name, role, prompt, ASCII art, SEO meta, favicon |
| Messages | Contact form submissions |
| Import Demo Content | Re-seed selected sections with placeholder content |

### PDF CV generator

Hit **Generate** in the admin panel and DomPDF renders your terminal content — experience, education, skills, projects, contact — to a clean A4 PDF. The `cv` link appears automatically in the terminal nav once the file exists. One source of truth for your portfolio and your resume.

#### CV font

**Settings → CV → CV font** picks the typeface: Helvetica, Times, Courier, or the bundled DejaVu Sans / Serif / Mono. Helvetica, Times and Courier only cover Western European text, so a CV containing Greek, Cyrillic, Turkish, etc. automatically switches to the matching DejaVu font; pick a DejaVu font if you want the same look in every language.

#### CV in other languages

The site itself stays single-language: write your content in the language set by `APP_LOCALE` (default `en`). To also produce your CV in other languages:

1. **Settings → CV → Additional CV languages** — choose the languages (the list lives in `config/portfolio.php`).
2. Each switched-on language adds a **CV translations** tab to Settings (name, role, about, section titles) and to every experience, education, project, skill and contact form. Anything you leave empty falls back to your main-language text, so a half-translated CV still renders completely.
3. On the dashboard, the CV widget gets a **Download** button per language. These PDFs are rendered on the spot and sent to your browser — they are never stored or published. Only the main-language CV is served at `/cv`.

Section titles (Objective, Experience, …) come from `lang/<locale>/cv.php`. To use your own wording, fill in **Settings → CV → … title** (main language) or the matching input in a translation tab; the built-in title is shown as the placeholder and is used whenever the input is empty.

To add a language, add it to `cv_locales` in `config/portfolio.php` and create `lang/<locale>/cv.php`. Right-to-left and CJK scripts (Arabic, Hebrew, Chinese, Japanese, Korean…) are not supported: DomPDF cannot shape them and the bundled fonts do not include them.

> **Upgrading an existing install:** run `php artisan migrate`. It adds the translation columns and the new settings rows without touching your content. Avoid re-running `SystemSeeder` on a live site — it resets terminal command labels and descriptions.

---

## Local development (Sail / Docker)

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan optimize
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan optimize
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Visit `http://localhost` — admin at `http://localhost/admin`.

---

Default admin password is `password`; you are forced to change it on first login.

### Seeding content

```bash
# Placeholder / demo content (fictitious data, safe to share)
./vendor/bin/sail artisan db:seed --class=ContentSeeder        # or: sail artisan ...

# Your real content (edit the seeder with your own data first)
./vendor/bin/sail  artisan db:seed --class=PersonalContentSeeder
```

Both seeders truncate their tables before inserting. Settings files are wiped from disk and re-uploaded from `public/stubs/`; project media is deleted (including files) and re-attached.

You can also re-seed individual sections from the admin panel at **Content → Import Demo Content**, which lets you pick which sections to replace without touching the rest.

### Environment variables

```dotenv
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laratermio
DB_USERNAME=root
DB_PASSWORD=

# Resend — outgoing mail for the contact form
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=you@yourdomain.com

# Admin account, created on first seed.
# Contact form messages from the terminal are delivered to this address.
ADMIN_EMAIL=foobar@yourdomain.com
ADMIN_NAME="Foo Bar"
```

### Running checks

```bash
# All checks — lint, static analysis, tests
./vendor/bin/sail composer test
# Static analysis (PHPStan / Larastan)
./vendor/bin/sail composer types:check
# or directly:
./vendor/bin/sail bin phpstan analyse --memory-limit 1G
```