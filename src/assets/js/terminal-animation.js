const PHPGIT_ASCII = [
    " ██████╗ ██╗  ██╗██████╗  ██████╗ ██╗████████╗",
    "  ██╔══██╗██║  ██║██╔══██╗██╔════╝ ██║╚══██╔══╝",
    "  ██████╔╝███████║██████╔╝██║  ███╗██║   ██║",
    "  ██╔═══╝ ██╔══██║██╔═══╝ ██║   ██║██║   ██║",
    "  ██║     ██║  ██║██║     ╚██████╔╝██║   ██║",
    "  ╚═╝     ╚═╝  ╚═╝╚═╝      ╚═════╝ ╚═╝   ╚═╝"
].join("\n");

function PHPGitTerminal(containerId) {
    const output = document.getElementById(containerId);

    function sleep(ms) {
        return new Promise(function (resolve) {
            setTimeout(resolve, ms);
        });
    }

    function doScroll() {
        const body = output.closest(".phpgit-terminal-body");
        if (body) {
            body.scrollTop = body.scrollHeight;
        }
    }

    function makeEl(tag, cls) {
        const el = document.createElement(tag);
        if (cls) {
            el.className = cls;
        }
        output.appendChild(el);
        doScroll();
        return el;
    }

    function def(v, fallback) {
        if (v === undefined) {
            return fallback;
        }
        return v;
    }

    async function typeLine(command, speed) {
        const sp = def(speed, 58);
        const line = makeEl("div", "term-line");
        line.innerHTML = [
            "<span class='term-host'>phpgit</span>",
            "<span class='term-dim'>@</span>",
            "<span class='term-dim'>~</span>",
            "<span class='term-muted'> $ </span>"
        ].join("");

        const cmd = document.createElement("span");
        cmd.className = "term-cmd";
        const cur = document.createElement("span");
        cur.className = "term-cursor";
        line.appendChild(cmd);
        line.appendChild(cur);

        await sleep(250);
        let ci = 0;
        while (ci < command.length) {
            cmd.textContent += command[ci];
            doScroll();
            await sleep(sp + Math.random() * 38);
            ci += 1;
        }
        cur.remove();
        await sleep(320);
    }

    async function out(html, delay, cls) {
        await sleep(def(delay, 30));
        const el = makeEl("div", def(cls, "term-line"));
        el.innerHTML = html;
        doScroll();
    }

    async function blank(delay) {
        await out("&nbsp;", def(delay, 0));
    }

    async function ascii(delay) {
        await sleep(def(delay, 0));
        const pre = document.createElement("pre");
        pre.className = "term-ascii";
        pre.textContent = PHPGIT_ASCII;
        output.appendChild(pre);
        doScroll();
    }

    async function progress(label, total) {
        const tot = def(total, 800);
        const d = makeEl("div", "term-line");
        const steps = 25;
        await sleep(0);
        let pi = 0;
        while (pi < steps) {
            const fill = "█".repeat(pi) + "░".repeat(steps - pi);
            const pct = String(Math.round(pi / steps * 100)).padStart(3, " ");
            d.innerHTML = [
                "<span class='term-bar-fill'>" + fill + "</span>",
                "<span class='term-dim'> " + pct + "%  </span>",
                "<span class='term-dim'>" + label + "</span>"
            ].join("");
            doScroll();
            await sleep(tot / steps);
            pi += 1;
        }
        d.innerHTML = [
            "<span class='term-bar-fill'>" + "█".repeat(steps) + "</span>",
            "<span class='term-dim'> 100%  </span>",
            "<span class='term-dim'>" + label + "</span>"
        ].join("");
        doScroll();
    }

    function cursor() {
        const d = makeEl("div", "term-line");
        d.innerHTML = [
            "<span class='term-host'>phpgit</span>",
            "<span class='term-dim'>@</span>",
            "<span class='term-dim'>~</span>",
            "<span class='term-muted'> $ </span>",
            "<span class='term-cursor'></span>"
        ].join("");
        doScroll();
    }

    return {
        run: async function (seq) {
            let ri = 0;
            while (ri < seq.length) {
                const s = seq[ri];
                if (s.t === "type") {
                    await typeLine(s.cmd, s.speed);
                } else if (s.t === "out") {
                    await out(s.html, def(s.delay, 35));
                } else if (s.t === "blank") {
                    await blank(def(s.delay, 0));
                } else if (s.t === "ascii") {
                    await ascii(def(s.delay, 0));
                } else if (s.t === "progress") {
                    await progress(s.label, def(s.dur, 900));
                } else if (s.t === "pause") {
                    await sleep(def(s.ms, 500));
                }
                ri += 1;
            }
            await cursor();
        }
    };
}

const PHPGIT_SERVE_SEQUENCE = [
    {ms: 900, t: "pause"},
    {cmd: "phpgit serve --env=production", speed: 120, t: "type"},
    {t: "blank"},
    {delay: 200, t: "ascii"},
    {
        delay: 120,
        html: [
            "<span class='term-dim'>",
            "                           v1.0.0  \u2022  MIT License",
            "</span>"
        ].join(""),
        t: "out"
    },
    {delay: 40, t: "blank"},
    {
        delay: 500,
        html: "<span class='term-dim'>Booting PHPGit server\u2026</span>",
        t: "out"
    },
    {
        delay: 600,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Reading",
            " <span class='term-info'>.env</span>",
            " configuration</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 480,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Environment:",
            " <span class='term-ok'>production</span></span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 420,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>PHP",
            " <span class='term-info'>8.4.3</span>",
            " runtime detected</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 380,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Composer autoloader",
            " registered</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 340,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Error handler bound</span>"
        ].join(""),
        t: "out"
    },
    {delay: 200, t: "blank"},
    {
        delay: 500,
        html: [
            "<span class='term-db'>  [ DB ]</span>",
            " <span class='term-dim'>Initialising database",
            " layer\u2026</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 420,
        html: [
            "<span class='term-db'>  [ DB ]</span>",
            " <span class='term-dim'>Driver:",
            " <span class='term-info'>pdo_mysql</span></span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 380,
        html: [
            "<span class='term-db'>  [ DB ]</span>",
            " <span class='term-dim'>Host:",
            " <span class='term-info'>localhost:3306</span></span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 440,
        html: [
            "<span class='term-db'>  [ DB ]</span>",
            " <span class='term-dim'>Connecting\u2026</span>"
        ].join(""),
        t: "out"
    },
    {dur: 2200, label: "establishing TCP connection", t: "progress"},
    {
        delay: 300,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Connected to",
            " <span class='term-info'>phpgit_db</span>",
            "@localhost</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 320,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Charset:",
            " <span class='term-info'>utf8mb4</span></span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 520,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Running schema",
            " check\u2026</span>"
        ].join(""),
        t: "out"
    },
    {dur: 1400, label: "verifying tables", t: "progress"},
    {
        delay: 300,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Schema version",
            " <span class='term-info'>12</span>",
            "  all tables present</span>"
        ].join(""),
        t: "out"
    },
    {delay: 200, t: "blank"},
    {
        delay: 500,
        html: [
            "<span class='term-dim'>  Starting",
            " services\u2026</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 520,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Session handler started",
            " <span class='term-muted'>(lifetime 30d)</span></span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 440,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>CSRF protection",
            " enabled</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 400,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Rate limiter",
            " initialised</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 420,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Auth middleware",
            " registered</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 460,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Git service",
            " initialised</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 400,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Page router loaded",
            " <span class='term-muted'>(14 routes)</span></span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 380,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>API router v1 loaded</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 360,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Static assets mounted</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 380,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Dev panel disabled",
            " <span class='term-muted'>(production)</span></span>"
        ].join(""),
        t: "out"
    },
    {delay: 200, t: "blank"},
    {
        delay: 600,
        html: [
            "<span class='term-http'>  [HTTP]</span>",
            " <span class='term-dim'>Binding to",
            " <span class='term-url'>http://0.0.0.0:8080</span>",
            "\u2026</span>"
        ].join(""),
        t: "out"
    },
    {dur: 1600, label: "starting HTTP listener", t: "progress"},
    {
        delay: 300,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Listening on",
            " <span class='term-url'>",
            "http://localhost:8080</span></span>"
        ].join(""),
        t: "out"
    },
    {delay: 200, t: "blank"},
    {
        delay: 400,
        html: [
            "<span class='term-success'>",
            "  \u2713  PHPGit is ready!</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 120,
        html: [
            "<span class='term-dim'>     Press Ctrl+C to",
            " stop the server.</span>"
        ].join(""),
        t: "out"
    },
    {delay: 100, t: "blank"}
];

const PHPGIT_INSTALL_SEQUENCE = [
    {ms: 900, t: "pause"},
    {cmd: "phpgit install --fresh", speed: 85, t: "type"},
    {t: "blank"},
    {delay: 200, t: "ascii"},
    {
        delay: 120,
        html: [
            "<span class='term-dim'>",
            "                           v1.0.0  \u2022  Installer",
            "</span>"
        ].join(""),
        t: "out"
    },
    {delay: 40, t: "blank"},
    {
        delay: 500,
        html: [
            "<span class='term-info'>",
            "  PHPGit Installer v1.0.0</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 600,
        html: [
            "<span class='term-dim'>  Checking system",
            " requirements\u2026</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 520,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>PHP",
            " <span class='term-info'>8.4.3</span>",
            "                  \u2713</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 400,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>PDO extension",
            "                \u2713</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 380,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>pdo_mysql driver",
            "             \u2713</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 360,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>OpenSSL",
            "                      \u2713</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 360,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>ctype extension",
            "              \u2713</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 340,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>mbstring extension",
            "           \u2713</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 360,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Writable storage path",
            "        \u2713</span>"
        ].join(""),
        t: "out"
    },
    {delay: 200, t: "blank"},
    {
        delay: 600,
        html: [
            "<span class='term-dim'>  Fetching dependencies",
            " via Composer\u2026</span>"
        ].join(""),
        t: "out"
    },
    {dur: 1800, label: "resolving packages", t: "progress"},
    {dur: 2400, label: "downloading packages", t: "progress"},
    {
        delay: 400,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>vlucas/phpdotenv",
            " installed</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 380,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>symfony/polyfill-mbstring",
            " installed</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 360,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>graham-campbell/result-type",
            " installed</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 340,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>",
            "<span class='term-info'>12</span>",
            " packages installed  autoloader updated</span>"
        ].join(""),
        t: "out"
    },
    {delay: 200, t: "blank"},
    {
        delay: 700,
        html: [
            "<span class='term-db'>  [ DB ]</span>",
            " <span class='term-dim'>Configuring database",
            " connection\u2026</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 520,
        html: [
            "<span class='term-db'>  [ DB ]</span>",
            " <span class='term-dim'>Testing connection to",
            " <span class='term-info'>localhost:3306</span>",
            "\u2026</span>"
        ].join(""),
        t: "out"
    },
    {dur: 1800, label: "connecting to MySQL", t: "progress"},
    {
        delay: 340,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Connection successful</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 600,
        html: [
            "<span class='term-db'>  [ DB ]</span>",
            " <span class='term-dim'>Running schema",
            " migrations\u2026</span>"
        ].join(""),
        t: "out"
    },
    {dur: 2600, label: "applying migrations  [0/12]", t: "progress"},
    {
        delay: 340,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Migrations applied",
            " <span class='term-info'>[12/12]</span>  \u2713</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 520,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Seed data inserted</span>"
        ].join(""),
        t: "out"
    },
    {delay: 200, t: "blank"},
    {
        delay: 600,
        html: [
            "<span class='term-dim'>  Generating application",
            " secrets\u2026</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 700,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>APP_KEY generated",
            " <span class='term-muted'>(base64:256-bit)</span></span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 520,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Session secret",
            " generated</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 480,
        html: [
            "<span class='term-ok'>  [ OK ]</span>",
            " <span class='term-dim'>Configuration written",
            " \u2192 <span class='term-info'>.env</span></span>"
        ].join(""),
        t: "out"
    },
    {delay: 200, t: "blank"},
    {
        delay: 400,
        html: [
            "<span class='term-success'>",
            "  \u2713  PHPGit installed successfully!</span>"
        ].join(""),
        t: "out"
    },
    {
        delay: 120,
        html: [
            "<span class='term-dim'>     Run",
            " <span class='term-cmd'>phpgit serve</span>",
            " to start the server.</span>"
        ].join(""),
        t: "out"
    },
    {delay: 100, t: "blank"}
];
