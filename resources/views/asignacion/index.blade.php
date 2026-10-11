<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Asignación de Carga - Transportes Castillo</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    @viteReactRefresh
    @vite([
        'resources/css/app.css',
        'resources/css/asignacion.css',
        'resources/js/asignacion/index.jsx'
    ])

    <script>
        (function() {
            try {
                const savedState = JSON.parse(localStorage.getItem("sidebarGroups") || "{}");
                // Insertamos una regla CSS dinámica inmediata para los grupos que estén colapsados
                let css = '';
                for (const group in savedState) {
                    if (savedState[group] === false && group !== 'encomiendas') {
                        // Oculta de inmediato antes del primer render
                        css += `.sidebar-group[data-group="${group}"] .sidebar-link { display: none !important; }`;
                        css += `.sidebar-group[data-group="${group}"] .sidebar-group-arrow { transform: rotate(-90deg) !important; }`;
                    }
                }
                if (css) {
                    const style = document.createElement('style');
                    style.id = 'sidebar-anti-flicker';
                    style.textContent = css;
                    document.head.appendChild(style);
                }
            } catch (e) {}
        })();
    </script>


</head>
<body>
    <div class="app-shell">
        @include('layouts.sidebar')
        <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

        <main class="app-main">
            <div id="asignacion-root"></div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const savedState = JSON.parse(localStorage.getItem("sidebarGroups") || "{}");
            const activeGroup = document.querySelector(".sidebar-link.active")?.closest(".sidebar-group")?.dataset.group;

            // 1. Restaurar las clases oficiales
            document.querySelectorAll(".sidebar-group").forEach((group) => {
                const groupName = group.dataset.group;
                const isCollapsed = savedState[groupName] === false;

                if (isCollapsed && groupName !== activeGroup) {
                    group.classList.add("collapsed");
                }
            });

            // 2. Retirar el parche anti-parpadeo una vez aplicadas las clases
            const flickerStyle = document.getElementById('sidebar-anti-flicker');
            flickerStyle?.remove();

            // 3. Escuchar clics en los grupos y guardar en localStorage
            document.querySelectorAll("[data-toggle-group]").forEach((button) => {
                button.addEventListener("click", () => {
                    const group = button.closest(".sidebar-group");
                    if (!group) return;

                    group.classList.toggle("collapsed");

                    const state = JSON.parse(localStorage.getItem("sidebarGroups") || "{}");
                    state[group.dataset.group] = !group.classList.contains("collapsed");
                    localStorage.setItem("sidebarGroups", JSON.stringify(state));
                });
            });

            // 4. Toggle responsive móvil
            const sidebar = document.querySelector(".sidebar");
            const backdrop = document.getElementById("sidebar-backdrop");
            const toggles = document.querySelectorAll(".sidebar-toggle");

            toggles.forEach(btn => {
                btn.addEventListener("click", () => {
                    sidebar?.classList.toggle("open");
                    backdrop?.classList.toggle("open");
                });
            });

            backdrop?.addEventListener("click", () => {
                sidebar?.classList.remove("open");
                backdrop?.classList.remove("open");
            });
        });
    </script>
</body>
</html>