(function () {
    var btn = document.getElementById("theme-toggle");
    if (!btn) {
        return;
    }

    function applyTheme(theme) {
        var icon;
        document.documentElement.setAttribute("data-bs-theme", theme);
        localStorage.setItem("theme", theme);
        icon = btn.querySelector("i");
        if (icon) {
            icon.className = (
                theme === "dark"
                ? "bi bi-sun"
                : "bi bi-moon-stars"
            );
        }
        btn.title = (
            theme === "dark"
            ? "Switch to light mode"
            : "Switch to dark mode"
        );
    }

    applyTheme(
        document.documentElement.getAttribute("data-bs-theme") || "dark"
    );

    btn.addEventListener("click", function () {
        var next = (
            document.documentElement.getAttribute("data-bs-theme") === "dark"
            ? "light"
            : "dark"
        );
        applyTheme(next);
    });
}());
