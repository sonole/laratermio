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

# Only if Cloudflare sits in front of the app, see "Deploying behind Cloudflare" below.
LARAVEL_CLOUDFLARE_ENABLED=false
```

### Deploying behind Cloudflare

Rate limits and the admin login throttle work per visitor IP, and HTTPS detection comes from the `X-Forwarded-Proto` header. Both are only as honest as whoever wrote the headers, so the app believes only the proxies that really sit in front of it (`App\Http\Middleware\TrustProxies`, the same class as in the other avstelematics apps):

- **Cloudflare**, by its published address ranges. They come from [monicahq/laravel-cloudflare](https://github.com/monicahq/laravel-cloudflare): fetched from cloudflare.com on the first request, cached, and refreshed weekly by the scheduler (`cloudflare:reload`, so `schedule:run` must be on your cron). Run `php artisan cloudflare:reload` after a deploy and after anything that clears the cache, so no visitor request has to. It is on by default; set `LARAVEL_CLOUDFLARE_ENABLED=false` to switch it off.
- **Loopback and the server's own address**, always. Plesk runs nginx in front of Apache on the server's public address, so PHP sees the proxy as the server's *own* address, not loopback and not Cloudflare, and without this every visitor would be that one address: the contact form limits and the admin login throttle shared by everybody. A connection from the machine itself cannot have come from anywhere else, so its headers are believed; anybody else's are ignored, which makes it safe where there is no such proxy too.

Nothing else is believed: with no proxy in front, a visitor could choose their own IP.

Only `X-Forwarded-For` and `X-Forwarded-Proto` are taken from the proxies (`trustProxies(headers: ...)` in `bootstrap/app.php`). A forwarded host or port would let a visitor choose the site's own host in its links, so they are ignored even from a proxy that is trusted.

Leave `LARAVEL_CLOUDFLARE_REPLACE_IP` off. It takes `CF-Connecting-IP` as the visitor's address from anyone, so a visitor who reaches the server directly could pick their own IP. The addresses Cloudflare puts in `X-Forwarded-For` are enough. The one setup this does not cover is a proxy that *overwrites* `X-Forwarded-For` with the Cloudflare address it saw instead of adding to it: visitors then share their Cloudflare edge's IP.

Any other proxy in front (a platform's load balancer, Traefik, ...) is not trusted, so every visitor would look like the proxy's IP and the site would think it is served over plain HTTP. Name it with Laravel's own `trustProxies` in `bootstrap/app.php`; the package adds Cloudflare's ranges to it:

```php
$middleware->trustProxies(at: ['REMOTE_ADDR'], headers: ...);
```

`REMOTE_ADDR` trusts whoever is connecting, so use it only when the app cannot be reached except through that proxy.

The contact form limits (`CONTACT_MAX_PER_IP_PER_HOUR`, `CONTACT_MAX_PER_DAY`) and the Secure session cookie (on automatically when `APP_URL` starts with `https://`) are described in `.env.example`.

### Running checks

```bash
# All checks — lint, static analysis, tests
 APP_CONFIG_CACHE=/tmp/no-config.php DB_CONNECTION=sqlite DB_DATABASE=:memory: ./vendor/bin/pest

# Static analysis (PHPStan / Larastan)
./vendor/bin/sail composer types:check
# or directly:
./vendor/bin/sail bin phpstan analyse --memory-limit 1G
```