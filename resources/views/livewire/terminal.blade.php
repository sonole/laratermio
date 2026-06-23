<div class="app-shell">

    <div class="terminal-nav">
        {{-- macOS-style window control dots --}}
        <div class="terminal-nav-left">
            <button class="terminal-nav-dot dot-red" onclick="window.termClose()" title="Close tab">
                <svg class="dot-icon" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1.5 1.5L6.5 6.5M6.5 1.5L1.5 6.5" stroke="rgba(0,0,0,0.55)" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>
            <button class="terminal-nav-dot dot-yellow" onclick="window.termMinimize()" title="Minimize window">
                <svg class="dot-icon" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <line x1="1.5" y1="4" x2="6.5" y2="4" stroke="rgba(0,0,0,0.55)" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </button>
            <button class="terminal-nav-dot dot-green" onclick="window.termFullscreen()" title="Fullscreen">
                <svg class="dot-icon" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 2.5V1H2.5M5.5 1H7V2.5M7 5.5V7H5.5M2.5 7H1V5.5" stroke="rgba(0,0,0,0.55)" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <div class="terminal-nav-cmds">
            @foreach($nav['commandItems'] as $item)
                <button class="terminal-btn"
                        onclick="window.termNavExec('{{ $item['exec'] }}')"
                        @disabled($navCommandsDisabled)>
                    {{ $item['label'] }}
                </button>
            @endforeach
        </div>

        <div class="terminal-nav-right">
            @foreach($nav['linkItems'] as $item)
                <a href="{{ $item['url'] }}"
                   class="terminal-cv-btn"
                   target="{{ $item['target'] }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- wire:ignore prevents Livewire from morphing jQuery Terminal's DOM --}}
    <div wire:ignore id="portfolio-terminal" class="terminal-screen"></div>

    {{-- Lightbox modal for project images and videos --}}
    <div id="t-modal" style="display:none">
        <div class="t-modal-backdrop"></div>
        <div class="t-modal-inner">
            <img class="t-modal-img" src="" alt="" style="display:none">
            <div class="t-modal-video" style="display:none"></div>
        </div>
    </div>

</div>

@script
<script>
    const ascii           = @json($asciiArt);
    const greeting        = @json($greeting);
    const HEADER_TEMPLATE = @json($headerTemplate);
    const TERMINAL_PROMPT = @json($terminalPrompt);
    const COMMANDS        = @json($commandNames);
    const FS_ROOTS        = @json($filesystemRoots);

    // ── Theme helpers ─────────────────────────────────────────────────────────
    function applyTheme(mode) {
        localStorage.setItem('terminal-theme', mode);
        let resolved = mode;
        if (mode === 'system') {
            resolved = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        }
        if (resolved === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    }

    // ── Utilities ─────────────────────────────────────────────────────────────
    const delay = ms => new Promise(r => setTimeout(r, ms));

    const echoHeader = (term, title) =>
        term.echo(HEADER_TEMPLATE.replace('__TITLE__', title), { raw: true });

    async function typeFormatted(term, str, speed) {
        // Parse into segments: { open: '[[b;#fff;]' | '', text: 'visible chars' }
        const segments = [];
        let i = 0;
        while (i < str.length) {
            if (str[i] === '[' && str[i + 1] === '[') {
                const headerEnd = str.indexOf(']', i + 2);
                const contentEnd = str.indexOf(']', headerEnd + 1);
                segments.push({ open: str.slice(i, headerEnd + 1), text: str.slice(headerEnd + 1, contentEnd) });
                i = contentEnd + 1;
            } else {
                const next = str.indexOf('[[', i);
                const end = next === -1 ? str.length : next;
                if (end > i) { segments.push({ open: '', text: str.slice(i, end) }); }
                i = end === i ? str.length : end;
            }
        }

        term.echo(' ');

        for (let si = 0; si < segments.length; si++) {
            for (let ci = 0; ci < segments[si].text.length; ci++) {
                let display = '';
                for (let k = 0; k < si; k++) {
                    const s = segments[k];
                    display += s.open ? `${s.open}${s.text}]` : s.text;
                }
                const partial = segments[si].text.slice(0, ci + 1);
                display += segments[si].open ? `${segments[si].open}${partial}]` : partial;
                term.update(-1, display);
                await delay(speed);
            }
        }
    }

    // ── Module-level state for interactive list selector ─────────────────────
    // Keydown is handled via a capture-phase DOM listener (added after terminal
    // init) because jQuery Terminal processes arrow/ctrl keys internally before
    // its own `keydown` option fires — capture phase intercepts them first.
    let selectorState = null;
    let selectorSeq = 0;
    let paginatorActive = false;
    let matrixActive = false;

    // ── Paginated reveal — one item at a time ─────────────────────────────────
    function paginate(term, items) {
        $wire.set('navCommandsDisabled', true);
        let idx = 0;
        const isTouchDevice = navigator.maxTouchPoints > 0;

        function showNext() {
            if (idx >= items.length) {
                $wire.set('navCommandsDisabled', false);
                term.resume();
                return;
            }

            if (idx > 0) term.echo('');
            term.echo(items[idx].html, { raw: true });
            idx++;

            if (idx >= items.length) {
                term.echo(`[[;#4b5563;]── ${idx}/${items.length} ─]`);
                paginatorActive = false;
                $wire.set('navCommandsDisabled', false);
                term.resume();
                return;
            }

            const counter = idx > 1 ? `${idx}/${items.length} · ` : '';

            if (isTouchDevice) {
                term.echo(
                    `<span onclick="window.termTapNext()" style="cursor:pointer;display:block;padding:2px 0;color:#4b5563">── ${counter}tap: next  ·  a: all  ·  q: quit ─</span>`,
                    { raw: true }
                );
            }

            paginatorActive = true;
            term.push(function(cmd) {
                const c = cmd.trim().toLowerCase();
                paginatorActive = false;
                if (c === 'q') { term.pop(); $wire.set('navCommandsDisabled', false); term.resume(); return; }
                if (c === 'a') {
                    term.pop();
                    items.slice(idx).forEach(item => term.echo(item.html, { raw: true }));
                    idx = items.length;
                    $wire.set('navCommandsDisabled', false);
                    term.resume();
                    return;
                }
                if (c === '') { term.pop(); showNext(); return; }
                term.echo('[[;#6b7280;]Enter: next  ·  a: all  ·  q: quit]');
                paginatorActive = true;
            }, {
                prompt: isTouchDevice ? '' : `[[;#4b5563;]── ${counter}Enter: next  ·  a: all  ·  q: quit ─]`,
                name: 'paginator',
            });
        }

        showNext();
    }

    // ── Interactive list selector — arrow keys + Enter ─────────────────────────
    function selector(term, items, initialSelected = 0) {
        $wire.set('navCommandsDisabled', true);
        const N = items.length;

        if (N === 0) {
            $wire.set('navCommandsDisabled', false);
            return;
        }

        const selectorId = ++selectorSeq;
        selectorState = {
            items, N,
            selected: initialSelected,
            selectorId,
            lineIdx: i => -(N - i + 2),  // nav=-1, ''=-2, item[N-1]=-3 … item[0]=-(N+2)
            rowText: null,
        };

        selectorState.rowText = function(i) {
            const p   = items[i];
            const sel = (i === selectorState.selected);
            const cursor = sel ? '<b style="color:#4ade80">❯</b>' : '<span style="color:#0d1117">❯</span>';
            const num    = sel ? `<b style="color:#4ade80">[${p.n}]</b>` : `<span style="color:#6b7280">[${p.n}]</span>`;
            const name   = sel ? `<b style="color:#e2e8f0">${p.name}</b>` : `<span style="color:#4b5563">${p.name}</span>`;
            const sub    = `<span style="color:#4b5563"> — ${p.subtitle}</span>`;
            return `<span id="sel-row-${selectorId}-${i}" onclick="termTapSelect(${i + 1})" style="cursor:pointer;display:block;padding:2px 0">  ${cursor} ${num} ${name}${sub}</span>`;
        };

        selectorState.updateRow = function(i) {
            const el = document.getElementById(`sel-row-${selectorId}-${i}`);
            if (el) el.outerHTML = selectorState.rowText(i);
        };

        for (let i = 0; i < N; i++) term.echo(selectorState.rowText(i), { raw: true });
        term.echo('');
        const isTouchDevice = navigator.maxTouchPoints > 0;
        term.echo(isTouchDevice
            ? '[[;#4b5563;]  tap to open an item  ·  q: quit]'
            : '[[;#4b5563;]  ↑ ↓: navigate  ·  Enter: open  ·  q: quit]'
        );

        term.push(function(cmd) {
            const c     = cmd.trim().toLowerCase();
            const state = selectorState;
            const sel   = state ? state.selected : 0;

            term.pop();
            selectorState = null;

            if (!state || c === 'q' || c === 'quit') {
                $wire.set('navCommandsDisabled', false);
                return;
            }

            if (c === '') {
                $wire.set('navCommandsDisabled', false);
                term.echo(state.items[sel].html, { raw: true });
                return;
            }

            const n = parseInt(c);
            if (!isNaN(n) && n >= 1 && n <= state.N) {
                $wire.set('navCommandsDisabled', false);
                term.echo(state.items[n - 1].html, { raw: true });
                return;
            }

            // Invalid input — re-show the selector preserving the current selection (stays disabled)
            selector(term, items, sel);
        }, { prompt: '' });
    }

    // ── Matrix rain overlay ───────────────────────────────────────────────────
    function startMatrix(term) {
        if (matrixActive) return;
        matrixActive = true;

        const overlay = document.createElement('div');
        overlay.id = 'matrix-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:#000;display:flex;align-items:center;justify-content:center';

        const canvas = document.createElement('canvas');
        overlay.appendChild(canvas);

        const isTouchDevice = navigator.maxTouchPoints > 0;

        const hint = document.createElement('div');
        hint.textContent = isTouchDevice ? 'tap to exit' : 'Ctrl+C to exit';
        hint.style.cssText = 'position:absolute;bottom:20px;left:50%;transform:translateX(-50%);color:rgba(74,222,128,0.4);font:12px monospace;pointer-events:none';
        overlay.appendChild(hint);

        document.body.appendChild(overlay);

        const ctx = canvas.getContext('2d');
        const resize = () => { canvas.width = window.innerWidth; canvas.height = window.innerHeight; };
        resize();
        window.addEventListener('resize', resize);

        const fontSize = 14;
        let columns = Math.floor(canvas.width / fontSize);
        const drops = Array.from({ length: columns }, () => Math.random() * -50);
        const chars = 'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワン0123456789ABCDEF';

        const frame = setInterval(() => {
            columns = Math.floor(canvas.width / fontSize);
            ctx.fillStyle = 'rgba(0,0,0,0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#4ade80';
            ctx.font = fontSize + 'px monospace';
            for (let i = 0; i < drops.length; i++) {
                const char = chars[Math.floor(Math.random() * chars.length)];
                ctx.fillText(char, i * fontSize, drops[i] * fontSize);
                if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) drops[i] = 0;
                drops[i]++;
            }
        }, 40);

        function stopMatrix() {
            if (!matrixActive) return;
            matrixActive = false;
            clearInterval(frame);
            window.removeEventListener('resize', resize);
            overlay.remove();
            term.resume();
            term.focus();
        }

        if (isTouchDevice) {
            overlay.addEventListener('touchend', (e) => { e.preventDefault(); stopMatrix(); }, { passive: false });
        }

        // Expose stopMatrix for the Ctrl+C capture handler (desktop) or external callers
        overlay._stop = stopMatrix;
    }

    function stopMatrixIfActive() {
        const overlay = document.getElementById('matrix-overlay');
        if (overlay && overlay._stop) {
            overlay._stop();
        }
    }

    // ── Response dispatch table ───────────────────────────────────────────────
    const responseHandlers = {
        echo: async (term, r) => {
            await delay(60);
            term.echo(r.html, { raw: true });
            term.resume();
        },
        clear: (term, _r) => {
            term.clear();
            setTimeout(function() {
                let s = document.querySelector('#portfolio-terminal .terminal-scroller');
                if (s) { s.scrollTop = 0; }
            }, 0);
            term.resume();
        },
        open: (term, r) => {
            window.open(r.url, '_blank', 'noopener,noreferrer');
            term.echo(`[[;#4ade80;]Opening ${r.url}…]`);
            term.resume();
        },
        theme: (term, r) => {
            applyTheme(r.key);
            term.echo(`[[;#4ade80;]Theme set to ${r.key}.]`);
            term.resume();
        },
        paginate: async (term, r) => {
            let items;
            try {
                items = await $wire.getStructuredData(r.key);
            } catch (_) {
                term.resume();
                term.echo(`[[;#f87171;]error: could not load ${r.key} data]`);
                return;
            }
            term.resume();
            echoHeader(term, r.key);
            paginate(term, items);
        },
        selector: async (term, r) => {
            let items;
            try {
                items = await $wire.getStructuredData(r.key);
            } catch (_) {
                term.resume();
                term.echo(`[[;#f87171;]error: could not load ${r.key} data]`);
                return;
            }
            term.resume();
            echoHeader(term, r.key);
            selector(term, items);
        },
        overlay: (term, r) => {
            if (r.key === 'cmatrix') {
                startMatrix(term);
                // term.resume() is called by stopMatrix
            } else {
                term.resume();
            }
        },
        cd: (term, r) => {
            term.set_prompt(r.html);
            term.resume();
        },
        client_history: (term, _r) => {
            const entries = term.history().data();
            if (!entries || entries.length === 0) {
                term.echo('[[;#6b7280;]no history yet]');
                term.resume();
                return;
            }
            const HEADER_TEMPLATE_HIST = HEADER_TEMPLATE.replace('__TITLE__', 'history');
            term.echo(HEADER_TEMPLATE_HIST, { raw: true });
            const rows = entries.map((cmd, i) => {
                const n = String(i + 1).padStart(4, ' ');
                return `[[;#6b7280;]${n}]  [[;#e2e8f0;]${$.terminal.escape_brackets(cmd)}]`;
            }).join('\n');
            term.echo(rows);
            term.resume();
        },
    };

    // ── Terminal init ─────────────────────────────────────────────────────────
    const term = $('#portfolio-terminal').terminal(
        async (command, term) => {
            if (!command.trim()) return;

            term.pause();
            const result = await $wire.execute(command.trim());
            const handler = responseHandlers[result.type] ?? responseHandlers.echo;
            await handler(term, result);
        },
        {
            greetings: false,
            prompt: TERMINAL_PROMPT,
            checkArity: false,
            historySize: 50,
            completion: function(string, callback) {
                // 'cd <partial>' → complete directory names
                const cdMatch = string.match(/^cd\s+(\S*)$/);
                if (cdMatch) {
                    const partial = cdMatch[1];
                    const dirs = FS_ROOTS.concat(['..', '~']);
                    callback(dirs.filter(d => d.startsWith(partial)).map(d => 'cd ' + d));
                    return;
                }
                // Complete command names from the first word
                callback(COMMANDS.filter(c => c.startsWith(string)));
            },

            onInit: function(term) {
                (async () => {
                    $wire.set('navCommandsDisabled', true);
                    term.freeze(true);

                    if (ascii.lines.length > 0) {
                        const escapeHtml = s => s.replace(/[&<>"]/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]));
                        const escaped = ascii.lines.map(escapeHtml);
                        const preStyle = `color:${ascii.color};font-size:${ascii.size};line-height:1;margin:0;display:inline-block`;
                        term.echo(`<pre id="ascii-art-block" style="${preStyle}">${escaped[0]}</pre>`, { raw: true });
                        for (let i = 1; i < escaped.length; i++) {
                            await delay(5);
                            const el = document.getElementById('ascii-art-block');
                            if (el) el.innerHTML = escaped.slice(0, i + 1).join('\n');
                        }
                        await delay(100);
                    }
                    await typeFormatted(term, `[[b;#e2e8f0;]${greeting.name}]`, 40);
                    await delay(50);
                    await typeFormatted(term, `[[;#4ade80;]${greeting.role}]`, 28);

                    term.freeze(false);
                    $wire.set('navCommandsDisabled', false);
                    setTimeout(function() {
                        let s = document.querySelector('#portfolio-terminal .terminal-scroller');
                        if (s) { s.scrollTop = 0; }
                    }, 100);
                })();
            },
        }
    );

    window.termInstance = term;

    // ── Mobile tap-to-focus fix ───────────────────────────────────────────────
    // jquery.terminal's touchend.terminal handler toggles enabled/disabled on
    // every tap: if the terminal was enabled when touch started, it calls
    // clip.blur() + self.disable(). This breaks the flow on mobile:
    //   run command → term.resume() re-enables → user taps to type → terminal disabled.
    // Fix: after the library's synchronous handler runs, re-enable if needed.
    // Guard against paginator/selector modes (level > 1 or active flags) so we
    // don't pop the keyboard up during "tap: next" or row-tap interactions.
    if (navigator.maxTouchPoints > 0) {
        document.getElementById('portfolio-terminal').addEventListener('touchend', function() {
            setTimeout(function() {
                const t = window.termInstance;
                if (t && !t.paused() && !t.enabled() && t.level() === 1 && !paginatorActive && !selectorState) {
                    // clip.blur() leaves cmd-editable at top:100% (off the bottom of the
                    // cmd container). Focusing it there causes iOS to scroll the terminal
                    // to bring it into view — the snap. Reset the inline top first so the
                    // element is already in the visible area when focused.
                    const editable = document.querySelector('#portfolio-terminal .cmd-editable');
                    if (editable) {
                        editable.style.removeProperty('top');
                    }
                    t.focus();
                }
            }, 50);
        });
    }

    // ── Project gallery interactions ──────────────────────────────────────────
    function openVideoModal(type, src) {
        const $modal = $('#t-modal');
        const $mImg  = $modal.find('.t-modal-img');
        const $mVid  = $modal.find('.t-modal-video');
        $mImg.hide();
        if (type === 'youtube' || type === 'vimeo') {
            $mVid.html('<iframe src="' + src + '" border="0" allowfullscreen allow="autoplay; encrypted-media" style="width:100%;height:100%;display:block"></iframe>').show();
        } else {
            $mVid.html('<video src="' + src + '" controls autoplay style="width:100%;height:100%;display:block;border-radius:6px"></video>').show();
        }
        $modal.fadeIn(150);
    }

    $(document).on('click', '.t-project-thumb', function () {
        const $wrap = $(this).closest('.t-project-media');
        $wrap.find('.t-project-thumb').removeClass('t-thumb-active');
        $(this).addClass('t-thumb-active');
        const type = $(this).data('type');
        const $img = $wrap.find('.t-project-img');
        const $vid = $wrap.find('.t-project-video-embed');
        if (type === 'image') {
            $vid.hide().empty();
            $img.attr('src', $(this).data('src'))
                .data('full', $(this).data('full') || $(this).data('src'))
                .show();
        } else if (type === 'youtube' || type === 'vimeo') {
            openVideoModal(type, $(this).data('embed'));
        } else if (type === 'video') {
            openVideoModal(type, $(this).data('src'));
        }
    });

    $(document).on('click', '.t-project-img', function () {
        const $modal = $('#t-modal');
        const $mImg  = $modal.find('.t-modal-img');
        const $mVid  = $modal.find('.t-modal-video');
        $mVid.hide().empty();
        $mImg.attr('src', $(this).data('full') || $(this).attr('src')).show();
        $modal.fadeIn(150);
    });

    $(document).on('click', '#t-modal', function () {
        $('#t-modal').find('.t-modal-video').empty().fadeOut(150);
        $('#t-modal').fadeOut(150);
    });

    // ── Capture-phase keyboard handler ────────────────────────────────────────
    // Registered on document in the CAPTURE phase so it runs BEFORE jQuery
    // Terminal's internal key handling (history nav, cursor movement).
    // This is the only reliable way to intercept arrow keys and Ctrl+C.
    document.addEventListener('keydown', function(e) {
        const t = window.termInstance;
        if (!t) return;

        // SIGINT — Ctrl+C cancels matrix, paginator, or selector modes
        if (e.ctrlKey && e.key.toLowerCase() === 'c') {
            if (matrixActive) {
                stopMatrixIfActive();
                t.echo('^C');
                e.preventDefault();
                e.stopImmediatePropagation();
                return;
            }
            selectorState = null;
            paginatorActive = false;
            while (t.level() > 1) t.pop();
            t.resume();
            $wire.set('navCommandsDisabled', false);
            t.echo('^C');
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }

        // Arrow keys — only intercept when inside the projects selector
        if (selectorState && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
            const { N, rowText, lineIdx } = selectorState;
            const prev = selectorState.selected;
            if (e.key === 'ArrowUp'   && prev > 0)     selectorState.selected--;
            if (e.key === 'ArrowDown' && prev < N - 1) selectorState.selected++;
            if (selectorState.selected !== prev) {
                selectorState.updateRow(prev);
                selectorState.updateRow(selectorState.selected);
            }
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true); // true = capture phase

    // ── Nav button execution ──────────────────────────────────────────────────
    window.termNavExec = (cmd) => {
        if (!window.termInstance) return;
        window.termInstance.history().append(cmd);
        window.termInstance.exec(cmd, false);
    };

    // Tap handler for paginator on mobile
    window.termTapNext = () => {
        if (!window.termInstance || !paginatorActive) return;
        window.termInstance.exec('', false);
    };

    // Tap handler for project rows on mobile
    window.termTapSelect = (n) => {
        if (!window.termInstance || !selectorState) return;
        const prev = selectorState.selected;
        selectorState.selected = n - 1;
        if (prev !== selectorState.selected) {
            selectorState.updateRow(prev);
            selectorState.updateRow(selectorState.selected);
        }
        window.termInstance.exec(String(n), false);
    };

    // ── Window control handlers ───────────────────────────────────────────────
    window.termClose = () => {
        window.close();
        setTimeout(() => {
            if (!window.closed) {
                term.echo('[[;#6b7280;]  Use [[b;#e2e8f0;]Ctrl+W] (or [[b;#e2e8f0;]⌘W] on Mac) to close this tab.]');
            }
        }, 80);
    };

    window.termMinimize = () => {
        term.echo('[[;#6b7280;]  Use [[b;#e2e8f0;]⌘M] (Mac) or [[b;#e2e8f0;]Win+↓] (Windows) to minimize the window.]');
    };

    window.termFullscreen = () => {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            document.documentElement.requestFullscreen?.();
        }
    };

</script>
@endscript
