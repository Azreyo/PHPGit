const PHPGIT_ASCII = ` ██████╗ ██╗  ██╗██████╗  ██████╗ ██╗████████╗
 ██╔══██╗██║  ██║██╔══██╗██╔════╝ ██║╚══██╔══╝
 ██████╔╝███████║██████╔╝██║  ███╗██║   ██║   
 ██╔═══╝ ██╔══██║██╔═══╝ ██║   ██║██║   ██║   
 ██║     ██║  ██║██║     ╚██████╔╝██║   ██║   
 ╚═╝     ╚═╝  ╚═╝╚═╝      ╚═════╝ ╚═╝   ╚═╝   `;

class PHPGitTerminal {
    constructor(containerId) {
        this.output = document.getElementById(containerId);
    }

    sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

    _scroll() {
        const body = this.output.closest('.phpgit-terminal-body');
        if (body) body.scrollTop = body.scrollHeight;
    }

    _el(tag = 'div', cls = '') {
        const el = document.createElement(tag);
        if (cls) el.className = cls;
        this.output.appendChild(el);
        this._scroll();
        return el;
    }

    async typeLine(command, speed = 58) {
        const line = this._el('div', 'term-line');
        line.innerHTML =
            `<span class="term-host">phpgit</span>` +
            `<span class="term-dim">@</span>` +
            `<span class="term-dim">~</span>` +
            `<span class="term-muted"> $ </span>`;

        const cmd = document.createElement('span');
        cmd.className = 'term-cmd';
        const cur = document.createElement('span');
        cur.className = 'term-cursor';
        line.appendChild(cmd);
        line.appendChild(cur);

        await this.sleep(250);
        for (const ch of command) {
            cmd.textContent += ch;
            this._scroll();
            await this.sleep(speed + Math.random() * 38);
        }
        cur.remove();
        await this.sleep(320);
    }

    async out(html, delay = 30, cls = 'term-line') {
        await this.sleep(delay);
        const d = this._el('div', cls);
        d.innerHTML = html;
        this._scroll();
    }

    async blank(delay = 0) { await this.out('&nbsp;', delay); }

    async ascii(delay = 0) {
        await this.sleep(delay);
        const pre = document.createElement('pre');
        pre.className = 'term-ascii';
        pre.textContent = PHPGIT_ASCII;
        this.output.appendChild(pre);
        this._scroll();
    }

    async progress(label, total = 800) {
        const d = this._el('div', 'term-line');
        const steps = 25;
        for (let i = 0; i <= steps; i++) {
            const fill = '█'.repeat(i) + '░'.repeat(steps - i);
            const pct  = String(Math.round(i / steps * 100)).padStart(3, ' ');
            d.innerHTML =
                `<span class="term-bar-fill">${fill}</span>` +
                `<span class="term-dim"> ${pct}%  </span>` +
                `<span class="term-dim">${label}</span>`;
            this._scroll();
            if (i < steps) await this.sleep(total / steps);
        }
    }

    async cursor() {
        const d = this._el('div', 'term-line');
        d.innerHTML =
            `<span class="term-host">phpgit</span>` +
            `<span class="term-dim">@</span>` +
            `<span class="term-dim">~</span>` +
            `<span class="term-muted"> $ </span>` +
            `<span class="term-cursor"></span>`;
        this._scroll();
    }

    async run(seq) {
        for (const s of seq) {
            switch (s.t) {
                case 'type':     await this.typeLine(s.cmd, s.speed); break;
                case 'out':      await this.out(s.html, s.delay ?? 35); break;
                case 'blank':    await this.blank(s.delay ?? 0); break;
                case 'ascii':    await this.ascii(s.delay ?? 0); break;
                case 'progress': await this.progress(s.label, s.dur ?? 900); break;
                case 'pause':    await this.sleep(s.ms ?? 500); break;
            }
        }
        await this.cursor();
    }
}

const PHPGIT_SERVE_SEQUENCE = [
    { t: 'pause',  ms: 900 },
    { t: 'type',   cmd: 'phpgit serve', speed: 90 },
    { t: 'blank' },
    { t: 'ascii',  delay: 200 },
    { t: 'out',    html: '<span class="term-dim">                           v1.0.0  •  MIT License</span>', delay: 120 },
    { t: 'blank',  delay: 40 },
    { t: 'out',    html: '<span class="term-dim">Booting PHPGit server…</span>', delay: 500 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Reading <span class="term-info">.env</span> configuration</span>', delay: 600 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Environment: <span class="term-ok">production</span></span>', delay: 480 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">PHP <span class="term-info">8.4.3</span> runtime detected</span>', delay: 420 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Composer autoloader registered</span>', delay: 380 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Error handler bound</span>', delay: 340 },
    { t: 'blank',  delay: 200 },
    { t: 'out',    html: '<span class="term-db">  [ DB ]</span> <span class="term-dim">Initialising database layer…</span>', delay: 500 },
    { t: 'out',    html: '<span class="term-db">  [ DB ]</span> <span class="term-dim">Driver: <span class="term-info">pdo_mysql</span></span>', delay: 420 },
    { t: 'out',    html: '<span class="term-db">  [ DB ]</span> <span class="term-dim">Host: <span class="term-info">localhost:3306</span></span>', delay: 380 },
    { t: 'out',    html: '<span class="term-db">  [ DB ]</span> <span class="term-dim">Connecting…</span>', delay: 440 },
    { t: 'progress', label: 'establishing TCP connection', dur: 2200 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Connected to <span class="term-info">phpgit_db</span>@localhost</span>', delay: 300 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Charset: <span class="term-info">utf8mb4</span></span>', delay: 320 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Running schema check…</span>', delay: 520 },
    { t: 'progress', label: 'verifying tables', dur: 1400 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Schema version <span class="term-info">12</span> · all tables present</span>', delay: 300 },
    { t: 'blank',  delay: 200 },
    { t: 'out',    html: '<span class="term-dim">  Starting services…</span>', delay: 500 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Session handler started <span class="term-muted">(lifetime 30d)</span></span>', delay: 520 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">CSRF protection enabled</span>', delay: 440 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Rate limiter initialised</span>', delay: 400 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Auth middleware registered</span>', delay: 420 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Git service initialised</span>', delay: 460 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Page router loaded <span class="term-muted">(14 routes)</span></span>', delay: 400 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">API router v1 loaded</span>', delay: 380 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Static assets mounted</span>', delay: 360 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Dev panel disabled <span class="term-muted">(production)</span></span>', delay: 380 },
    { t: 'blank',  delay: 200 },

    { t: 'out',    html: '<span class="term-http">  [HTTP]</span> <span class="term-dim">Binding to <span class="term-url">http://0.0.0.0:8080</span>…</span>', delay: 600 },
    { t: 'progress', label: 'starting HTTP listener', dur: 1600 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Listening on <span class="term-url">http://localhost:8080</span></span>', delay: 300 },
    { t: 'blank',  delay: 200 },
    { t: 'out',    html: '<span class="term-success">  ✓  PHPGit is ready!</span>', delay: 400 },
    { t: 'out',    html: '<span class="term-dim">     Press Ctrl+C to stop the server.</span>', delay: 120 },
    { t: 'blank',  delay: 100 },
];

const PHPGIT_INSTALL_SEQUENCE = [
    { t: 'pause',  ms: 900 },
    { t: 'type',   cmd: 'phpgit install --fresh', speed: 85 },
    { t: 'blank' },
    { t: 'ascii',  delay: 200 },
    { t: 'out',    html: '<span class="term-dim">                           v1.0.0  •  Installer</span>', delay: 120 },
    { t: 'blank',  delay: 40 },
    { t: 'out',    html: '<span class="term-info">  PHPGit Installer v1.0.0</span>', delay: 500 },
    { t: 'out',    html: '<span class="term-dim">  Checking system requirements…</span>', delay: 600 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">PHP <span class="term-info">8.4.3</span>                  ✓</span>', delay: 520 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">PDO extension                ✓</span>', delay: 400 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">pdo_mysql driver             ✓</span>', delay: 380 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">OpenSSL                      ✓</span>', delay: 360 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">ctype extension              ✓</span>', delay: 360 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">mbstring extension           ✓</span>', delay: 340 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Writable storage path        ✓</span>', delay: 360 },
    { t: 'blank',  delay: 200 },
    { t: 'out',    html: '<span class="term-dim">  Fetching dependencies via Composer…</span>', delay: 600 },
    { t: 'progress', label: 'resolving packages', dur: 1800 },
    { t: 'progress', label: 'downloading packages', dur: 2400 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">vlucas/phpdotenv installed</span>', delay: 400 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">symfony/polyfill-mbstring installed</span>', delay: 380 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">graham-campbell/result-type installed</span>', delay: 360 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim"><span class="term-info">12</span> packages installed · autoloader updated</span>', delay: 340 },
    { t: 'blank',  delay: 200 },
    { t: 'out',    html: '<span class="term-db">  [ DB ]</span> <span class="term-dim">Configuring database connection…</span>', delay: 700 },
    { t: 'out',    html: '<span class="term-db">  [ DB ]</span> <span class="term-dim">Testing connection to <span class="term-info">localhost:3306</span>…</span>', delay: 520 },
    { t: 'progress', label: 'connecting to MySQL', dur: 1800 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Connection successful</span>', delay: 340 },
    { t: 'out',    html: '<span class="term-db">  [ DB ]</span> <span class="term-dim">Running schema migrations…</span>', delay: 600 },
    { t: 'progress', label: 'applying migrations  [0/12]', dur: 2600 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Migrations applied <span class="term-info">[12/12]</span>  ✓</span>', delay: 340 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Seed data inserted</span>', delay: 520 },
    { t: 'blank',  delay: 200 },
    { t: 'out',    html: '<span class="term-dim">  Generating application secrets…</span>', delay: 600 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">APP_KEY generated <span class="term-muted">(base64:256-bit)</span></span>', delay: 700 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Session secret generated</span>', delay: 520 },
    { t: 'out',    html: '<span class="term-ok">  [ OK ]</span> <span class="term-dim">Configuration written → <span class="term-info">.env</span></span>', delay: 480 },
    { t: 'blank',  delay: 200 },
    { t: 'out',    html: '<span class="term-success">  ✓  PHPGit installed successfully!</span>', delay: 400 },
    { t: 'out',    html: '<span class="term-dim">     Run <span class="term-cmd">phpgit serve</span> to start the server.</span>', delay: 120 },
    { t: 'blank',  delay: 100 },
];
