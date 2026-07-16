/*jslint browser, devel, long*/
/*global hljs*/
(function () {
    "use strict";
    var root = document.getElementById("repo-view");
    var source;
    var table;
    if (!root) {
        return;
    }

    function urlFor(proto) {
        if (proto === "ssh") {
            return root.dataset.sshUrl;
        }
        return root.dataset.httpUrl;
    }

    function copy(text) {
        if (navigator.clipboard && text) {
            navigator.clipboard.writeText(text);
        }
    }

    document.querySelectorAll(".js-clone-proto").forEach(function (element) {
        element.addEventListener("click", function () {
            var value = urlFor(element.dataset.proto);
            var display = document.getElementById("cloneUrlDisplay");
            if (display) {
                display.textContent = value;
            }
            document.querySelectorAll(".clone-url-inline").forEach(function (inline) {
                inline.textContent = value;
            });
        });
    });
    document.querySelectorAll(".js-clone-tab").forEach(function (tab) {
        tab.addEventListener("click", function (event) {
            var input = document.getElementById("cloneUrlInput");
            event.preventDefault();
            if (input) {
                input.value = urlFor(tab.dataset.proto);
            }
            tab.closest(".nav").querySelectorAll(".nav-link").forEach(function (link) {
                link.classList.remove("active");
            });
            tab.classList.add("active");
        });
    });
    document.querySelectorAll(".js-copy-text").forEach(function (button) {
        button.addEventListener("click", function () {
            copy(button.dataset.copyText);
        });
    });
    document.querySelectorAll(".js-copy-clone-display").forEach(function (button) {
        button.addEventListener("click", function () {
            var display = document.getElementById("cloneUrlDisplay");
            if (display) {
                copy(display.textContent.trim());
            }
        });
    });
    document.querySelectorAll(".js-copy-clone-input").forEach(function (button) {
        button.addEventListener("click", function () {
            var input = document.getElementById("cloneUrlInput");
            if (input) {
                copy(input.value);
            }
        });
    });
    document.querySelectorAll(".js-copy-file").forEach(function (button) {
        button.addEventListener("click", function () {
            var code = document.getElementById("rv-hl-src");
            if (code) {
                copy(code.textContent);
            }
        });
    });
    document.querySelectorAll(".rv-line-num").forEach(function (line) {
        line.addEventListener("click", function () {
            var row = line.closest("tr");
            if (row) {
                row.classList.toggle("rv-line-highlighted");
            }
        });
    });
    document.querySelectorAll(".js-tree-toggle").forEach(function (link) {
        link.addEventListener("click", function (event) {
            if (link.parentElement.querySelector(".rv-tree-children")) {
                event.preventDefault();
                window.location.href = link.href;
            }
        });
    });
    document.querySelectorAll(".js-confirm-delete").forEach(function (button) {
        button.addEventListener("click", function (event) {
            if (!window.confirm("Are you sure you want to delete this repository?")) {
                event.preventDefault();
            }
        });
    });

    source = document.getElementById("rv-hl-src");
    table = document.getElementById("rv-code-table");
    if (source && table && hljs !== undefined) {
        hljs.highlightElement(source);
        source.innerHTML.split("\n").forEach(function (line, index) {
            var cells = table.querySelectorAll(".rv-line-code");
            if (cells[index]) {
                cells[index].innerHTML = line;
            }
        });
    }
}());
