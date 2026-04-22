function setCloneUrl(proto) {
    const url = (proto === "ssh" ? SSH_URL : HTTP_URL);
    const el = document.getElementById("cloneUrlDisplay");
    if (el) {
        el.textContent = url;
    }
    document.querySelectorAll(".clone-url-inline")
        .forEach((e) => e.textContent = url);
}

function copyCloneUrl(btn) {
    const el = document.getElementById("cloneUrlDisplay");
    if (!el) {
        return;
    }
    navigator.clipboard.writeText(el.textContent.trim()).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = "<i class='bi bi-check2 text-success'></i>";
        setTimeout(() => btn.innerHTML = orig, 1500);
    });
}

function switchCloneTab(proto, tabEl) {
    const url = proto === "ssh" ? SSH_URL : HTTP_URL;
    const inp = document.getElementById("cloneUrlInput");
    if (inp) {
        inp.value = url;
    }
    tabEl.closest(".nav")
        .querySelectorAll(".nav-link")
        .forEach((l) => l.classList.remove("active"));
    tabEl.classList.add("active");
}

function copyCloneInput() {
    const inp = document.getElementById("cloneUrlInput");
    if (!inp) {
        return;
    }
    navigator.clipboard.writeText(inp.value);
}

function rvCopyFile(btn) {
    const src = document.getElementById("rv-hl-src");
    if (!src) return;
    navigator.clipboard.writeText(src.textContent).then(() => {
        const icon = document.getElementById("rv-copy-icon");
        if (icon) {
            icon.className = "bi bi-check2 text-success";
            setTimeout(() => {
                icon.className = "bi bi-clipboard";
            }, 1800);
        }
    });
}

function rvToggleLine(n) {
    const row = document.getElementById("L" + n);
    if (!row) return;
    row.classList.toggle("rv-line-highlighted");
}

function rvTreeToggle(el, e) {
    const li = el.parentElement;
    if (!li) return;
    const children = li.querySelector(".rv-tree-children");
    if (!children) return;
    e.preventDefault();
    const isOpen = children.classList.toggle("rv-open");
    const caret = el.querySelector(".rv-tree-toggle i");
    if (caret) {
        caret.className = (
            isOpen ? "bi bi-caret-down-fill" : "bi bi-caret-right-fill"
        );
    }
    window.location.href = el.href;
}

document.addEventListener("DOMContentLoaded", function () {
    const src = document.getElementById("rv-hl-src");
    const table = document.getElementById("rv-code-table");
    if (!src || !table || typeof hljs === "undefined") return;

    hljs.highlightElement(src);

    const highlightedLines = src.innerHTML.split("\n");
    const codeCells = table.querySelectorAll(".rv-line-code");
    codeCells.forEach(function (cell, i) {
        cell.innerHTML = (
            highlightedLines[i] !== undefined ? highlightedLines[i] : ""
        );
    });
});

(function () {
    const el = document.getElementById("hljs-css");
    if (!el) return;
    const t = document.documentElement.getAttribute("data-bs-theme");
    if (t === "light") {
        el.href =
            "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/" +
            "styles/github.min.css";
    }
})();

