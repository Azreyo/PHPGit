const overlay = document.getElementById("overlay");
const wheel = document.getElementById("wheel");

const GLOW_NORMAL = [
    "drop-shadow(0 0 10px rgba(210,165,45,.80))",
    "drop-shadow(0 0 30px rgba(210,165,45,.35))",
    "drop-shadow(0 0 70px rgba(160,110,20,.20))"
].join(" ");


overlay.classList.add("active");

wheel.style.animation = "none";
wheel.style.opacity = "0";
wheel.style.transform = "rotate(0deg) scale(0)";
wheel.style.filter = GLOW_NORMAL;
wheel.getBoundingClientRect(); // reset easter to visible base

wheel.style.animation = "summon 1.4s cubic-bezier(.22,1,.36,1) forwards";

wheel.addEventListener("animationend", function onSummon() {
    wheel.removeEventListener("animationend", onSummon);

    wheel.style.animation = "none";
    wheel.style.opacity = "1";
    wheel.style.transform = "rotate(0deg) scale(1)";
    wheel.style.filter = GLOW_NORMAL;
    wheel.getBoundingClientRect();

    wheel.style.animation = "adapt 2.6s cubic-bezier(.16,1,.3,1) forwards";

    wheel.addEventListener("animationend", function onAdapt() {
        wheel.removeEventListener("animationend", onAdapt);

        wheel.style.animation = "none";
        wheel.style.opacity = "1";
        wheel.style.transform = "rotate(0deg) scale(1)";
        wheel.style.filter = GLOW_NORMAL;

        overlay.classList.remove("active");
    });
});