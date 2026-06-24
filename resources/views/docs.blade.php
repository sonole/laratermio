<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="laratermio — a terminal-style personal portfolio built with Laravel.">
    <title>laratermio— docs</title>
    <style>
    :root {
      --ground:     #0C0E14;
      --ground-2:   #131620;
      --ground-3:   #1C1F2E;
      --text:       #E2E4ED;
      --muted:      #6B6F82;
      --border:     #21243A;
      --accent:     #F0A500;
      --accent-dim: rgba(240, 165, 0, 0.10);
      --accent-2:   #4F7BF7;
      --a2-dim:     rgba(79, 123, 247, 0.10);
      --green:      #4EC9A0;

      --mono: 'SF Mono', 'Cascadia Code', 'JetBrains Mono', 'Fira Code',
              ui-monospace, 'Courier New', monospace;
      --sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }

    body {
      background: var(--ground);
      color: var(--text);
      font-family: var(--sans);
      font-size: 15px;
      line-height: 1.7;
      -webkit-font-smoothing: antialiased;
    }

    a { color: var(--accent); text-decoration: none; }
    a:hover { text-decoration: underline; }

    .wrap {
      max-width: 880px;
      margin: 0 auto;
      padding: 0 28px;
    }

    /* ── Hero ── */
    .hero { padding: 88px 0 72px; }

    .hero-eyebrow {
      font-family: var(--mono);
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 28px;
      display: flex;
      align-items: center;
    }

    .e-user { color: var(--accent); }
    .e-sep  { color: var(--muted); }
    .e-path { color: var(--accent-2); }

    .caret {
      display: inline-block;
      width: 9px;
      height: 17px;
      background: var(--accent);
      margin-left: 7px;
      vertical-align: text-bottom;
      animation: blink 1.15s step-end infinite;
    }

    @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: 0; } }

    .hero h1 {
      font-family: var(--mono);
      font-size: clamp(44px, 8vw, 80px);
      font-weight: 700;
      letter-spacing: -0.03em;
      line-height: 1.0;
      color: var(--text);
      margin-bottom: 24px;
    }

    .hero h1 .hl { color: var(--accent); }

    .hero-sub {
      font-size: 17px;
      color: var(--muted);
      max-width: 520px;
      line-height: 1.65;
      margin-bottom: 36px;
    }

    .badges { display: flex; flex-wrap: wrap; gap: 8px; }

    .badge {
      font-family: var(--mono);
      font-size: 11px;
      padding: 4px 11px;
      border: 1px solid var(--border);
      border-radius: 4px;
      color: var(--muted);
    }

    .badge.hi {
      border-color: rgba(240, 165, 0, 0.5);
      color: var(--accent);
      background: var(--accent-dim);
    }

    /* ── Divider ── */
    .div { border: none; border-top: 1px solid var(--border); }

    /* ── Section ── */
    .section { padding: 64px 0; }

    .s-label {
      font-family: var(--mono);
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.14em;
      color: var(--accent);
      margin-bottom: 10px;
    }

    .s-title {
      font-family: var(--mono);
      font-size: 22px;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 14px;
    }

    .s-body {
      color: var(--muted);
      max-width: 580px;
      margin-bottom: 32px;
      line-height: 1.7;
    }

    /* ── Terminal block ── */
    .term {
      background: var(--ground-2);
      border: 1px solid var(--border);
      border-radius: 8px;
      overflow: hidden;
      font-family: var(--mono);
      font-size: 12.5px;
    }

    .term-bar {
      background: var(--ground-3);
      border-bottom: 1px solid var(--border);
      padding: 10px 16px;
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .dot { width: 10px; height: 10px; border-radius: 50%; }
    .dr  { background: #FF5F57; }
    .dy  { background: #FFBD2E; }
    .dg  { background: #28C840; }

    .term-tab {
      font-size: 11px;
      color: var(--muted);
      margin: 0 auto;
      padding-right: 30px;
    }

    .term-body { padding: 20px 24px; line-height: 1.95; }

    .t-prompt { color: var(--accent); }
    .t-cmd    { color: var(--text); }
    .t-out    { color: var(--muted); }
    .t-group  {
      color: var(--accent-2);
      margin-top: 14px;
      margin-bottom: 2px;
      display: block;
    }

    .t-row {
      display: grid;
      grid-template-columns: 240px 12px 1fr;
      padding-left: 10px;
    }

    .t-key  { color: var(--text); }
    .t-dash { color: var(--muted); text-align: center; }
    .t-val  { color: var(--muted); }

    /* ── Fastfetch card ── */
    .ff {
      background: var(--ground-2);
      border: 1px solid var(--border);
      border-radius: 8px;
      overflow: hidden;
      display: grid;
      grid-template-columns: auto 1fr;
      font-family: var(--mono);
      font-size: 12.5px;
    }

    @media (max-width: 620px) {
      .ff { grid-template-columns: 1fr; }
      .ff-logo { display: none; }
    }

    .ff-logo {
      border-right: 1px solid var(--border);
      padding: 28px 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--accent-dim);
    }

    .ff-logo pre {
      font-size: 5px;
      line-height: 1.2;
      color: var(--accent);
      white-space: pre;
      user-select: none;
    }

    .ff-info { padding: 28px 32px; line-height: 2.05; }

    .ff-user { color: var(--accent); font-weight: 600; }
    .ff-sep  { color: var(--border); }

    .ff-row { display: flex; }
    .ff-k   { color: var(--accent); min-width: 115px; }
    .ff-c   { color: var(--muted); margin-right: 8px; }
    .ff-v   { color: var(--text); }

    /* ── CV card ── */
    .cv-card {
      background: linear-gradient(130deg, var(--accent-dim) 0%, transparent 70%);
      border: 1px solid rgba(240, 165, 0, 0.28);
      border-radius: 8px;
      padding: 32px;
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 20px;
      align-items: center;
    }

    @media (max-width: 520px) { .cv-card { grid-template-columns: 1fr; } }

    .cv-card h3 {
      font-family: var(--mono);
      font-size: 15px;
      font-weight: 600;
      color: var(--accent);
      margin-bottom: 8px;
    }

    .cv-card p { color: var(--muted); font-size: 14px; line-height: 1.65; }

    .cv-pill {
      font-family: var(--mono);
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 700;
      padding: 7px 16px;
      background: var(--accent);
      color: var(--ground);
      border-radius: 4px;
      white-space: nowrap;
      align-self: start;
    }

    /* ── Setup steps ── */
    .steps {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
    }

    @media (max-width: 660px) { .steps { grid-template-columns: 1fr; } }

    .step {
      background: var(--ground-2);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 20px;
      font-family: var(--mono);
      font-size: 12px;
    }

    .step-n {
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--accent);
      margin-bottom: 10px;
    }

    .step-code { color: var(--muted); line-height: 2.1; }
    .sp  { color: var(--accent-2); }
    .sc  { color: var(--text); }
    .sc2 { color: var(--green); }

    /* ── Env block ── */
    .env {
      font-family: var(--mono);
      font-size: 12.5px;
      line-height: 2.1;
    }

    .ec { color: var(--muted); }
    .ek { color: var(--accent-2); }
    .ev { color: var(--green); }
    .eq { color: var(--muted); }

    /* ── Prose helpers ── */
    .hint {
      font-family: var(--mono);
      font-size: 12px;
      color: var(--muted);
      line-height: 1.8;
    }

    .sub-label {
      font-family: var(--mono);
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--accent-2);
      margin-bottom: 14px;
    }

    .sub-label-note {
      color: var(--muted);
      font-size: 10px;
      text-transform: none;
      letter-spacing: 0;
    }

    .body-sm {
      color: var(--muted);
      font-size: 14px;
      line-height: 1.7;
      max-width: 580px;
      margin-bottom: 16px;
    }

    /* ── Footer ── */
    .footer {
      padding: 40px 0 48px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-family: var(--mono);
      font-size: 11.5px;
      color: var(--muted);
      gap: 16px;
      flex-wrap: wrap;
    }

    /* ── Demo GIF ── */
    .demo-wrap {
        padding-bottom: 40px;
    }

    .demo-frame {
      background: var(--ground-2);
      border: 1px solid var(--border);
      border-radius: 10px;
      overflow: hidden;
    }

    .demo-bar {
      background: var(--ground-3);
      border-bottom: 1px solid var(--border);
      padding: 10px 16px;
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .demo-bar .term-tab { padding-right: 30px; }

    .demo-frame img {
      display: block;
      width: 100%;
      height: auto;
    }

    @media (prefers-reduced-motion: reduce) {
      .caret { animation: none; opacity: 1; }
    }
    </style>
</head>
<body>

<!-- Hero -->
<div class="wrap">
    <div class="hero">
        <div class="hero-eyebrow">
            <span class="e-user">visitor@laratermio</span><span class="e-sep">:</span><span class="e-path">~</span><span class="e-sep">$&nbsp;</span><span class="caret"></span>
        </div>
        <h1>lara<span class="hl">termio</span></h1>
        <p class="hero-sub">Your portfolio lives in the terminal. Visitors type commands to explore your work — no scrolling, no nav menus, no template layout.</p>
        <div class="badges">
            <a href="{{ route('home') }}" class="badge hi">live demo &rarr;</a>
            <img src="https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
            <img src="https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
            <img src="https://img.shields.io/badge/livewire-%234e56a6.svg?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire">
            <img src="https://img.shields.io/badge/filament-%23FDAE4B.svg?style=for-the-badge&logo=filament&logoColor=black&logoSize=auto" alt="Filament">
            <img src="https://img.shields.io/badge/tailwindcss-%2338B2AC.svg?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="TailwindCSS">
        </div>
    </div>
</div>

<!-- Demo -->
<div class="wrap">
    <div class="demo-wrap">
        <div class="demo-frame">
            <div class="demo-bar">
                <div class="dot dr"></div>
                <div class="dot dy"></div>
                <div class="dot dg"></div>
                <div class="term-tab">laratermio — demo</div>
            </div>
            <img src="{{ asset('demo.gif') }}" alt="laratermio demo">
        </div>
    </div>
</div>

<hr class="div">

<!-- Concept -->
<div class="wrap">
    <div class="section">
        <div class="s-label">concept</div>
        <div class="s-title">An interactive terminal, not a webpage</div>
        <p class="s-body">The entire portfolio runs inside a browser-based terminal emulator. A visitor arrives, sees a prompt, and starts typing. There are no hero images, no carousels, no "contact me" buttons. Just a cursor and commands — and the discipline to make that interesting.</p>

        <div class="term">
            <div class="term-bar">
                <div class="dot dr"></div>
                <div class="dot dy"></div>
                <div class="dot dg"></div>
                <div class="term-tab">laratermio — bash</div>
            </div>
            <div class="term-body">
                <div><span class="t-prompt">visitor@laratermio:~$&nbsp;</span><span class="t-cmd">help</span></div>
                <div class="t-out">&nbsp;</div>
                <span class="t-group">explore</span>
                <div class="t-row"><span class="t-key">about</span><span class="t-dash">—</span><span class="t-val">Who I am and what drives me</span></div>
                <div class="t-row"><span class="t-key">contact</span><span class="t-dash">—</span><span class="t-val">Get in touch</span></div>
                <div class="t-row"><span class="t-key">contact &lt;email&gt; &lt;message&gt;</span><span class="t-dash">—</span><span class="t-val">Drop your email and a short message</span></div>
                <div class="t-row"><span class="t-key">education</span><span class="t-dash">—</span><span class="t-val">Education & certifications</span></div>
                <div class="t-row"><span class="t-key">open &lt;name&gt;</span><span class="t-dash">—</span><span class="t-val">Open a link in a new tab</span></div>
                <div class="t-row"><span class="t-key">search &lt;query&gt;</span><span class="t-dash">—</span><span class="t-val">Search across education, experience, projects, skills</span></div>
                <div class="t-row"><span class="t-key">skills</span><span class="t-dash">—</span><span class="t-val">Technical skills and stack</span></div>
                <span class="t-group">experience</span>
                <div class="t-row"><span class="t-key">experience</span><span class="t-dash">—</span><span class="t-val">Work history</span></div>
                <div class="t-row"><span class="t-key">experience &lt;n&gt;</span><span class="t-dash">—</span><span class="t-val">Jump directly to experience n</span></div>
                <div class="t-row"><span class="t-key">experience -a</span><span class="t-dash">—</span><span class="t-val">Full work history at once</span></div>
                <span class="t-group">projects</span>
                <div class="t-row"><span class="t-key">projects</span><span class="t-dash">—</span><span class="t-val">Side projects and open source</span></div>
                <div class="t-row"><span class="t-key">projects &lt;n&gt;</span><span class="t-dash">—</span><span class="t-val">Jump directly to project n</span></div>
                <div class="t-row"><span class="t-key">projects -a</span><span class="t-dash">—</span><span class="t-val">All projects at once</span></div>
                <span class="t-group">system</span>
                <div class="t-row"><span class="t-key">cd &lt;dir&gt;</span><span class="t-dash">—</span><span class="t-val">Navigate the portfolio filesystem</span></div>
                <div class="t-row"><span class="t-key">clear</span><span class="t-dash">—</span><span class="t-val">Clear the terminal screen</span></div>
                <div class="t-row"><span class="t-key">history</span><span class="t-dash">—</span><span class="t-val">Show command history</span></div>
                <div class="t-row"><span class="t-key">ls</span><span class="t-dash">—</span><span class="t-val">List contents of current directory</span></div>
                <div class="t-row"><span class="t-key">theme &lt;mode&gt;</span><span class="t-dash">—</span><span class="t-val">Switch color scheme (light / dark / system)</span></div>
                <div class="t-row"><span class="t-key">whoami</span><span class="t-dash">—</span><span class="t-val">Print current user identity</span></div>

            </div>
        </div>

        <p class="hint" style="margin-top:16px">
            <span style="color:var(--accent)">→</span> Curious? There are more commands that won't appear in <code style="color:var(--text)">help</code> — explore and find them.
        </p>
    </div>
</div>

<hr class="div">

<!-- Stack -->
<div class="wrap">
    <div class="section">
        <div class="s-label">stack</div>
        <div class="s-title">What it runs on</div>
        <p class="s-body">Managed through a Filament 5 admin panel. All content — experience, projects, skills, terminal commands — lives in the database and is fully editable without touching code.</p>

        <div class="ff">
            <div class="ff-logo">
                <pre>  ██████╗  ██████╗ ██████╗ ████████╗███████╗ ██████╗ ██╗     ██╗ ██████╗
  ██╔══██╗██╔═══██╗██╔══██╗╚══██╔══╝██╔════╝██╔═══██╗██║     ██║██╔═══██╗
  ██████╔╝██║   ██║██████╔╝   ██║   █████╗  ██║   ██║██║     ██║██║   ██║
  ██╔═══╝ ██║   ██║██╔══██╗   ██║   ██╔══╝  ██║   ██║██║     ██║██║   ██║
  ██║     ╚██████╔╝██║  ██║   ██║   ██║     ╚██████╔╝███████╗██║╚██████╔╝
  ╚═╝      ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚═╝      ╚═════╝ ╚══════╝╚═╝ ╚═════╝</pre>
            </div>
            <div class="ff-info">
                <div class="ff-user">visitor@laratermio</div>
                <div class="ff-sep">─────────────────────</div>
                <div class="ff-row"><span class="ff-k">Terminal</span><span class="ff-c">:</span><span class="ff-v">laratermio</span></div>
                <div class="ff-row"><span class="ff-k">Framework</span><span class="ff-c">:</span><span class="ff-v">Laravel 13</span></div>
                <div class="ff-row"><span class="ff-k">PHP</span><span class="ff-c">:</span><span class="ff-v">8.5</span></div>
                <div class="ff-row"><span class="ff-k">UI</span><span class="ff-c">:</span><span class="ff-v">Livewire 4</span></div>
                <div class="ff-row"><span class="ff-k">Admin</span><span class="ff-c">:</span><span class="ff-v">Filament 5</span></div>
                <div class="ff-row"><span class="ff-k">CSS</span><span class="ff-c">:</span><span class="ff-v">Tailwind 4</span></div>
                <div class="ff-row"><span class="ff-k">Shell</span><span class="ff-c">:</span><span class="ff-v">jQuery Terminal</span></div>
                <div class="ff-row"><span class="ff-k">Media</span><span class="ff-c">:</span><span class="ff-v">Spatie Media Library</span></div>
                <div class="ff-row"><span class="ff-k">Mail</span><span class="ff-c">:</span><span class="ff-v">Resend</span></div>
                <div class="ff-row"><span class="ff-k">Database</span><span class="ff-c">:</span><span class="ff-v">MySQL 8</span></div>
                <div class="ff-row"><span class="ff-k">Tests</span><span class="ff-c">:</span><span class="ff-v">Pest 4</span></div>
            </div>
        </div>
    </div>
</div>

<hr class="div">

<!-- CV Generator -->
<div class="wrap">
    <div class="section">
        <div class="s-label">feature</div>
        <div class="s-title">PDF CV — generated from your content</div>
        <p class="s-body">Your terminal content doubles as a CV. Hit "Generate" in the admin panel and DomPDF renders everything — experience, education, skills, projects, contact — to a clean A4 PDF. The CV link appears automatically in the terminal nav once the file exists. One source of truth for your portfolio and your resume.</p>

        <div class="cv-card">
            <div>
                <h3>$ cv</h3>
                <p>One button in the admin panel. Experience, education, skills, and projects rendered to A4. The terminal picks it up automatically — no config, no code change.</p>
            </div>
            <div class="cv-pill">PDF export</div>
        </div>
    </div>
</div>

<hr class="div">

<!-- Setup -->
<div class="wrap">
    <div class="section">
        <div class="s-label">local development</div>
        <div class="s-title">Run the project</div>

        <p class="sub-label">
            Sail / Docker <span class="sub-label-note">(recommended — no local MySQL needed)</span>
        </p>

        <div class="steps" style="margin-bottom:28px">
            <div class="step">
                <div class="step-n">step 1</div>
                <div class="step-code">
                    <div><span class="sp">$&nbsp;</span><span class="sc">git clone sonole/laratermio</span></div>
                    <div><span class="sp">$&nbsp;</span><span class="sc">cd laratermio</span></div>
                    <div><span class="sp">$&nbsp;</span><span class="sc">cp .env.example .env</span></div>
                    <div><span class="sp">$&nbsp;</span><span class="sc">sail up -d</span></div>
                    <div class="sc2">&nbsp;&nbsp;↳ app + MySQL containers</div>
                </div>
            </div>
            <div class="step">
                <div class="step-n">step 2</div>
                <div class="step-code">
                    <div><span class="sp">$&nbsp;</span><span class="sc">sail artisan optimize</span></div>
                    <div><span class="sp">$&nbsp;</span><span class="sc">sail artisan key:generate</span></div>
                    <div><span class="sp">$&nbsp;</span><span class="sc">sail artisan optimize</span></div>
                    <div><span class="sp">$&nbsp;</span><span class="sc">sail artisan migrate --seed</span></div>
                </div>
            </div>
            <div class="step">
                <div class="step-n">step 3</div>
                <div class="step-code">
                    <div><span class="sp">$&nbsp;</span><span class="sc">sail npm  install</span></div>
                    <div><span class="sp">$&nbsp;</span><span class="sc">sail npm run dev</span></div>
                    <div class="sc2">&nbsp;&nbsp;↳ visit localhost</div>
                </div>
            </div>
        </div>

        {{-- ── Seeding ── --}}
        <p class="s-label">seeding content</p>
        <p class="body-sm"><code style="color:var(--text)">db:seed</code> (and <code style="color:var(--text)">SystemSeeder</code>) only seeds the admin user and terminal commands. Portfolio content is seeded separately. Pick one:</p>

        <div class="term">
            <div class="term-bar">
                <div class="dot dr"></div>
                <div class="dot dy"></div>
                <div class="dot dg"></div>
                <div class="term-tab">content seeding options</div>
            </div>
            <div class="term-body">
                <div><span class="t-out"># Placeholder / demo content — fictitious data, safe to share</span></div>
                <div><span class="t-prompt">$&nbsp;</span><span class="t-cmd">php artisan db:seed --class=ContentSeeder</span></div>
                <div class="t-out">&nbsp;</div>
                <div><span class="t-out"># Your real content — edit PersonalContentSeeder with your own data first</span></div>
                <div><span class="t-prompt">$&nbsp;</span><span class="t-cmd">php artisan db:seed --class=PersonalContentSeeder</span></div>
                <div class="t-out">&nbsp;</div>
                <div><span class="t-out"># Or re-seed individual sections without touching the rest:</span></div>
                <div><span class="t-prompt">$&nbsp;</span><span class="t-cmd">open localhost/admin/import-demo-content</span></div>
            </div>
        </div>

        <p class="hint" style="margin-top:12px;margin-bottom:28px">Both seeders truncate their tables before inserting. Settings files and project media are deleted from disk and re-attached from <span style="color:var(--text)">public/stubs/</span>. Prefix with <span style="color:var(--text)">./vendor/bin/sail artisan</span> when using Sail.</p>

        {{-- ── Env ── --}}
        <div class="term">
            <div class="term-bar">
                <div class="dot dr"></div>
                <div class="dot dy"></div>
                <div class="dot dg"></div>
                <div class="term-tab">.env — required values</div>
            </div>
            <div class="term-body">
                <div class="env">
                    <div><span class="ec"># ── App ─────────────────────────────</span></div>
                    <div><span class="ek">APP_URL</span><span class="eq">=</span><span class="ev">http://localhost</span></div>
                    <div>&nbsp;</div>
                    <div><span class="ec"># ── Database ─────────────────────────</span></div>
                    <div><span class="ek">DB_HOST</span><span class="eq">=</span><span class="ev">mysql</span></div>
                    <div><span class="ek">DB_DATABASE</span><span class="eq">=</span><span class="ev">laratermio</span></div>
                    <div><span class="ek">DB_USERNAME</span><span class="eq">=</span><span class="ev">laratermio</span></div>
                    <div><span class="ek">DB_PASSWORD</span><span class="eq">=</span></div>

                    <div>&nbsp;</div>
                    <div><span class="ec"># ── Mail (Resend) ─────────────────────</span></div>
                    <div><span class="ek">RESEND_API_KEY</span><span class="eq">=</span><span class="ev">re_...</span></div>
                    <div><span class="ek">MAIL_FROM_ADDRESS</span><span class="eq">=</span><span class="ev">you@yourdomain.com</span></div>
                    <div>&nbsp;</div>
                    <div><span class="ec"># ── Admin (created on first seed) ─────</span></div>
                    <div><span class="ec"># Contact form messages are delivered here</span></div>
                    <div><span class="ek">ADMIN_EMAIL</span><span class="eq">=</span><span class="ev">you@yourdomain.com</span></div>
                    <div><span class="ek">ADMIN_NAME</span><span class="eq">=</span><span class="ev">"Your Name"</span></div>
                </div>
            </div>
        </div>

        <div style="height:16px"></div>
        <p class="hint">
            Admin at <span style="color:var(--text)">localhost/admin</span> &mdash; default password is <span style="color:var(--text)">password</span>, forced change on first login.
        </p>
    </div>
</div>

<hr class="div">

<!-- Testing -->
<div class="wrap">
    <div class="section" style="padding-bottom:32px">
        <div class="s-label">testing</div>
        <div class="s-title">Run the suite</div>

        <div class="term">
            <div class="term-bar">
                <div class="dot dr"></div>
                <div class="dot dy"></div>
                <div class="dot dg"></div>
                <div class="term-tab">laratermio — bash</div>
            </div>
            <div class="term-body">
                <div><span class="t-prompt">$&nbsp;</span><span class="t-cmd">sail composer run test</span><span class="t-out">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# pint + phpstan + pest</span></div>
                <div><span class="t-prompt">$&nbsp;</span><span class="t-cmd">sail artisan test --compact</span><span class="t-out">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# just pest</span></div>
                <div><span class="t-prompt">$&nbsp;</span><span class="t-cmd">sail composer run types:check</span><span class="t-out">&nbsp;&nbsp;# larastan only</span></div>
                <div class="t-out">&nbsp;</div>
                <div><span class="t-out"># or directly:</span></div>
                <div><span class="t-prompt">$&nbsp;</span><span class="t-cmd">sail bin phpstan analyse --memory-limit 1G</span></div>
            </div>
        </div>
    </div>
</div>

<hr class="div">

<!-- Footer -->
<div class="wrap">
    <div class="footer">
        <span>laratermio &mdash; MIT license</span>
        <span>built with <a href="https://laravel.com">Laravel</a> &middot; <a href="https://livewire.laravel.com">Livewire</a> &middot; <a href="https://filamentphp.com">Filament</a></span>
    </div>
</div>

</body>
</html>
