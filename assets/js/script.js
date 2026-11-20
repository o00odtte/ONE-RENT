document.addEventListener("DOMContentLoaded", function () {
    const dateElement = document.getElementById("currentDate");
    if (dateElement) {
        dateElement.textContent = new Date().toLocaleDateString("id-ID", {
            day: "numeric", month: "long", year: "numeric"
        });
    }

    const sidebar = document.getElementById("sidebar");
    document.querySelectorAll(".menu-toggle").forEach(function (button) {
        button.addEventListener("click", function () {
            if (sidebar) sidebar.classList.toggle("show");
        });
    });

    document.querySelectorAll(".alert-message, .customer-alert, .module-alert").forEach(function (alert) {
        window.setTimeout(function () {
            alert.style.transition = "opacity .3s ease, transform .3s ease";
            alert.style.opacity = "0";
            alert.style.transform = "translateY(-8px)";
            window.setTimeout(function () { alert.remove(); }, 300);
        }, 3000);
    });

    if (window.lucide) window.lucide.createIcons();
});

function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    if (sidebar) sidebar.classList.toggle("show");
}
