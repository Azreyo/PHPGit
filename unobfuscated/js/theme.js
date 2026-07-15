(function () {
    var btn = document.getElementById("theme-toggle");
    var themeInputs = Array.prototype.slice.call(
        document.querySelectorAll("input[name=\"theme\"]")
    );
    var fontFamilySelect = document.getElementById("appearance-font");
    var fontSizeSelect = document.getElementById("appearance-font-size");
    var densityInputs = Array.prototype.slice.call(
        document.querySelectorAll("input[name=\"density\"]")
    );
    var optAnimations = document.getElementById("opt-animations");
    var optTerminalAnim = document.getElementById("opt-terminal-anim");
    var optActivityGraph = document.getElementById("opt-activity-graph");
    var saveButton = document.getElementById("appearance-save");
    var resetButton = document.getElementById("appearance-reset");
    var mediaQuery;

    function resolveTheme(theme) {
        if (theme === "system") {
            if (
                window.matchMedia("(prefers-color-scheme: dark)").matches
            ) {
                return "dark";
            }
            return "light";
        }
        return theme;
    }

    function applyTheme(theme, persist) {
        var actual = resolveTheme(theme);
        var icon;

        document.documentElement.setAttribute("data-bs-theme", actual);
        if (persist) {
            localStorage.setItem("theme", theme);
        }

        if (btn) {
            icon = btn.querySelector("i");
            if (icon) {
                if (actual === "dark") {
                    icon.className = "bi bi-sun";
                } else {
                    icon.className = "bi bi-moon-stars";
                }
            }
            if (actual === "dark") {
                btn.title = "Switch to light mode";
            } else {
                btn.title = "Switch to dark mode";
            }
        }
        updateThemeRadios(theme);
    }

    function updateThemeRadios(theme) {
        themeInputs.forEach(function (input) {
            input.checked = input.value === theme;
        });
    }

    function applyCodeFontFamily(value) {
        var family;

        if (value === "monospace") {
            family = "var(--bs-font-monospace)";
        } else {
            family = "\"" + value + "\", monospace";
        }
        document.documentElement.style.setProperty(
            "--app-code-font-family",
            family
        );
    }

    function applyCodeFontSize(value) {
        if (!value) {
            return;
        }
        document.documentElement.style.setProperty(
            "--app-code-font-size",
            value + "px"
        );
    }

    function applyDensity(value) {
        if (!value) {
            value = "default";
        }
        document.documentElement.setAttribute("data-ui-density", value);
    }

    function applyAnimations(enabled) {
        if (enabled) {
            document.documentElement.setAttribute(
                "data-ui-animations",
                "true"
            );
        } else {
            document.documentElement.setAttribute(
                "data-ui-animations",
                "false"
            );
        }
    }

    function applyTerminalAnimation(enabled) {
        if (enabled) {
            document.documentElement.setAttribute(
                "data-terminal-animation",
                "true"
            );
        } else {
            document.documentElement.setAttribute(
                "data-terminal-animation",
                "false"
            );
        }
    }

    function applyActivityGraph(enabled) {
        if (enabled) {
            document.documentElement.setAttribute(
                "data-activity-graph",
                "true"
            );
        } else {
            document.documentElement.setAttribute(
                "data-activity-graph",
                "false"
            );
        }
    }

    function loadAppearanceSettings() {
        var activityEnabled;
        var animationsEnabled;
        var storedAnimations;
        var storedDensity;
        var storedFont;
        var storedFontSize;
        var storedGraph;
        var storedTheme;
        var storedTerminal;
        var terminalEnabled;

        storedTheme = localStorage.getItem("theme") || "dark";
        applyTheme(storedTheme, false);

        if (fontFamilySelect) {
            storedFont = (
                localStorage.getItem("appearanceFontFamily")
                || "monospace"
            );
            fontFamilySelect.value = storedFont;
            applyCodeFontFamily(storedFont);
        }

        if (fontSizeSelect) {
            storedFontSize = (
                localStorage.getItem("appearanceFontSize")
                || "13"
            );
            fontSizeSelect.value = storedFontSize;
            applyCodeFontSize(storedFontSize);
        }

        storedDensity = (
            localStorage.getItem("appearanceDensity")
            || "default"
        );
        densityInputs.forEach(function (input) {
            input.checked = input.value === storedDensity;
        });
        applyDensity(storedDensity);

        storedAnimations = localStorage.getItem("appearanceAnimations");
        animationsEnabled = storedAnimations !== "false";
        if (optAnimations) {
            optAnimations.checked = animationsEnabled;
        }
        applyAnimations(animationsEnabled);

        storedTerminal = localStorage.getItem("appearanceTerminalAnimation");
        terminalEnabled = storedTerminal !== "false";
        if (optTerminalAnim) {
            optTerminalAnim.checked = terminalEnabled;
        }
        applyTerminalAnimation(terminalEnabled);

        storedGraph = localStorage.getItem("appearanceActivityGraph");
        activityEnabled = storedGraph !== "false";
        if (optActivityGraph) {
            optActivityGraph.checked = activityEnabled;
        }
        applyActivityGraph(activityEnabled);
    }

    function storedThemeValue() {
        var selected;

        selected = themeInputs.find(function (input) {
            return input.checked;
        });
        if (selected) {
            return selected.value;
        }
        return "dark";
    }

    function saveAppearanceSettings() {
        var originalText;
        var selectedDensity;
        var selectedTheme;

        selectedTheme = storedThemeValue();
        if (selectedTheme) {
            localStorage.setItem("theme", selectedTheme);
            applyTheme(selectedTheme, false);
        }

        if (fontFamilySelect) {
            localStorage.setItem(
                "appearanceFontFamily",
                fontFamilySelect.value
            );
        }

        if (fontSizeSelect) {
            localStorage.setItem(
                "appearanceFontSize",
                fontSizeSelect.value
            );
        }

        selectedDensity = densityInputs.find(function (input) {
            return input.checked;
        });
        if (selectedDensity) {
            localStorage.setItem(
                "appearanceDensity",
                selectedDensity.value
            );
        }

        if (optAnimations) {
            if (optAnimations.checked) {
                localStorage.setItem(
                    "appearanceAnimations",
                    "true"
                );
            } else {
                localStorage.setItem(
                    "appearanceAnimations",
                    "false"
                );
            }
        }

        if (optTerminalAnim) {
            if (optTerminalAnim.checked) {
                localStorage.setItem(
                    "appearanceTerminalAnimation",
                    "true"
                );
            } else {
                localStorage.setItem(
                    "appearanceTerminalAnimation",
                    "false"
                );
            }
        }

        if (optActivityGraph) {
            if (optActivityGraph.checked) {
                localStorage.setItem(
                    "appearanceActivityGraph",
                    "true"
                );
            } else {
                localStorage.setItem(
                    "appearanceActivityGraph",
                    "false"
                );
            }
        }

        if (saveButton) {
            originalText = saveButton.innerHTML;
            saveButton.innerHTML = (
                "<i class=\"bi bi-check2-circle\"></i> "
                + "Saved"
            );
            window.setTimeout(function () {
                saveButton.innerHTML = originalText;
            }, 1400);
        }
    }

    function resetAppearanceSettings() {
        localStorage.removeItem("theme");
        localStorage.removeItem("appearanceFontFamily");
        localStorage.removeItem("appearanceFontSize");
        localStorage.removeItem("appearanceDensity");
        localStorage.removeItem("appearanceAnimations");
        localStorage.removeItem("appearanceTerminalAnimation");
        localStorage.removeItem("appearanceActivityGraph");
        loadAppearanceSettings();
    }

    if (btn) {
        btn.addEventListener("click", function () {
            var currentTheme;
            var next;

            currentTheme = document.documentElement.getAttribute(
                "data-bs-theme"
            );
            if (currentTheme === "dark") {
                next = "light";
            } else {
                next = "dark";
            }
            applyTheme(next, true);
        });
    }

    themeInputs.forEach(function (input) {
        input.addEventListener("change", function (event) {
            applyTheme(event.target.value, false);
        });
    });

    if (fontFamilySelect) {
        fontFamilySelect.addEventListener("change", function (event) {
            applyCodeFontFamily(event.target.value);
        });
    }

    if (fontSizeSelect) {
        fontSizeSelect.addEventListener("change", function (event) {
            applyCodeFontSize(event.target.value);
        });
    }

    densityInputs.forEach(function (input) {
        input.addEventListener("change", function (event) {
            applyDensity(event.target.value);
        });
    });

    if (optAnimations) {
        optAnimations.addEventListener("change", function (event) {
            applyAnimations(event.target.checked);
        });
    }

    if (optTerminalAnim) {
        optTerminalAnim.addEventListener("change", function (event) {
            applyTerminalAnimation(event.target.checked);
        });
    }

    if (optActivityGraph) {
        optActivityGraph.addEventListener("change", function (event) {
            applyActivityGraph(event.target.checked);
        });
    }

    if (saveButton) {
        saveButton.addEventListener("click", saveAppearanceSettings);
    }

    if (resetButton) {
        resetButton.addEventListener("click", function () {
            resetAppearanceSettings();
        });
    }

    mediaQuery = window.matchMedia && window.matchMedia(
        "(prefers-color-scheme: dark)"
    );
    if (mediaQuery && typeof mediaQuery.addEventListener === "function") {
        mediaQuery.addEventListener("change", function () {
            if (localStorage.getItem("theme") === "system") {
                applyTheme("system", false);
            }
        });
    } else if (mediaQuery && typeof mediaQuery.addListener === "function") {
        mediaQuery.addListener(function () {
            if (localStorage.getItem("theme") === "system") {
                applyTheme("system", false);
            }
        });
    }

    loadAppearanceSettings();
}());
