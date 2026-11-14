import Chart from 'chart.js/auto';  

const state = {
    envios: [],
    users: [],
    roles: [],
    userRoles: [],
    permissions: [],
    pendingAmounts: [],
    debtClients: [],
    debtMovements: [],
    selectedDebtClient: null,
    liquidaciones: [],
    liqEnvios: [],
    liqTransportistaId: null,
    gestionTransportistaId: null,
    gestionEnvios: [],
    maquinarias: [],
    alquileres: [],
    editingMaquinariaId: null,
    nextCode: "ENV-0001",
    editingId: null,
    editingUserId: null,
    editingRoleId: null,
    editingClienteId: null,
    editingCarrierId: null,
    liquidatingEnvioId: null,
    items: [{ cantidad: "", descripcion: "" }],
    pagination: { page: 1, lastPage: 1, total: 0 },
    filters: {
        q: "",
        pago: "",
        fecha_desde: "",
        fecha_hasta: "",
    },
    alquilerFilters: {
        q: "",
        pago: "",
        fecha_desde: "",
        fecha_hasta: "",
    }
};

let currentAbonoClientData = { id: null, nombre: '', deudaEnvios: 0, deudaAlquileres: 0, deudaTotal: 0 };
let currentMovementTab = 'todos';

const $ = (selector) => document.querySelector(selector);

function showConfirmModal(message) {
    return new Promise((resolve) => {
        const modal = $("#confirm-modal");
        const messageEl = $("#confirm-modal-message");
        const btnYes = $("#btn-confirm-yes");
        const btnNo = $("#btn-confirm-no");

        messageEl.textContent = message;
        modal.classList.add("open");
        modal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";

        const cleanup = () => {
            modal.classList.remove("open");
            modal.setAttribute("aria-hidden", "true");
            document.body.style.overflow = "";
            btnYes.removeEventListener("click", onYes);
            btnNo.removeEventListener("click", onNo);
        };

        const onYes = () => {
            cleanup();
            resolve(true);
        };

        const onNo = () => {
            cleanup();
            resolve(false);
        };

        btnYes.addEventListener("click", onYes);
        btnNo.addEventListener("click", onNo);
    });
}

const can = (permission) => (window.AppContext?.user?.permissions || []).includes(permission);
const on = (selector, event, callback) => {
    const element = $(selector);
    if (element) element.addEventListener(event, callback);
};

const api = {
    async request(path, options = {}) {
        const response = await fetch(`/api${path}`, {
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": document.querySelector("meta[name='csrf-token']")?.content || "",
            },
            ...options,
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.ok === false) {
            throw new Error(errorMessage(payload));
        }

        return payload;
    },

    get(path) {
        return this.request(path);
    },

    post(path, data) {
        return this.request(path, {
            method: "POST",
            body: JSON.stringify(data),
        });
    },

    put(path, data) {
        return this.request(path, {
            method: "PUT",
            body: JSON.stringify(data),
        });
    },

    patch(path, data) {
        return this.request(path, {
            method: "PATCH",
            body: JSON.stringify(data),
        });
    },

    delete(path) {
        return this.request(path, { method: "DELETE" });
    },
};

document.addEventListener("DOMContentLoaded", async () => {
    try {
        initLoginCanvas();
    } catch (e) {
        console.warn("Canvas login no disponible", e);
    }

    try {
        await loadTransportistasForLookup();
        initTransportistaSearch();
    } catch (e) {
        console.warn("Error cargando transportistas para lookup", e);
    }

    if (!$("#envio-form")) {
        return;
    }

    try {
        bindEvents();
    } catch (e) {
        console.error("Error en bindEvents:", e);
    }

    try {
        bindMaquinariaEvents();
    } catch (e) {
        console.error("Error en bindMaquinariaEvents:", e);
    }

    try {
        bindAlquilerEvents();
    } catch (e) {
        console.error("Error en bindAlquilerEvents:", e);
    }

    setToday();
    
    const urlParams = new URLSearchParams(window.location.search);
    const initialSection = urlParams.get("section") || window.location.hash.replace("#", "");

    if (initialSection) {
        showSection(initialSection);
    } else if (can("dashboard.view")) {
        showSection("dashboard");
    } else {
        showSection("envios");
    }

    // Cargas iniciales asíncronas seguras
    loadEnvios().catch(err => console.error("Error loadEnvios:", err));
    
    if (can("clientes.manage") || can("transportistas.manage") || can("envios.create")) {
        loadManagementLists().catch(err => console.error("Error loadManagementLists:", err));
    }
});

function initLoginCanvas() {
    const canvas = $("#login-canvas");
    if (!canvas) return;

    const context = canvas.getContext("2d");
    const shapes = [
        { x: .22, y: .28, w: .18, h: .18, color: "rgba(184, 132, 33, .12)", speed: .1 },
        { x: .64, y: .20, w: .20, h: .20, color: "rgba(32, 36, 44, .05)", speed: .08 },
        { x: .18, y: .72, w: .40, h: .003, color: "rgba(32, 36, 44, .14)", speed: .06 },
        { x: .72, y: .72, w: .08, h: .08, color: "rgba(184, 132, 33, .18)", speed: .12 },
    ];

    function resize() {
        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        canvas.width = Math.floor(rect.width * ratio);
        canvas.height = Math.floor(rect.height * ratio);
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
    }

    function draw(time = 0) {
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        context.clearRect(0, 0, width, height);

        shapes.forEach((shape, index) => {
            const drift = Math.sin((time / 1000) * shape.speed + index) * 8;
            context.fillStyle = shape.color;
            context.save();
            context.translate(width * shape.x + drift, height * shape.y);
            context.fillRect(0, 0, width * shape.w, height * shape.h);
            context.restore();
        });

        requestAnimationFrame(draw);
    }

    resize();
    window.addEventListener("resize", resize);
    draw();
}

function bindEvents() {
    document.querySelectorAll("[data-section]").forEach((button) => {
        button.addEventListener("click", () => showSection(button.dataset.section));
    });
    document.querySelectorAll("[data-toggle-group]").forEach((button) => {
        button.addEventListener("click", () => toggleSidebarGroup(button.dataset.toggleGroup));
    });
    on("#sidebar-toggle", "click", toggleSidebarMenu);
    on("#topbar-toggle", "click", toggleSidebarMenu);
    on("#sidebar-backdrop", "click", closeSidebarMenu);

    initSidebarGroups();

    setupAbonoModalEvents();
    setupMovementsTabs();

    const debouncedClientSearch = debounce(searchClient, 220);

    document.addEventListener("input", (event) => {
        if (event.target?.id === "f-cliente-search") {
            debouncedClientSearch();
        }
    });

    on("#btn-open-modal", "click", openModal);
    on("#btn-close-modal", "click", closeModal);
    on("#btn-cancel", "click", closeModal);
    on("#btn-open-client-modal", "click", openClientModal);
    on("#btn-close-client-modal", "click", closeClientModal);
    on("#btn-cancel-client-modal", "click", closeClientModal);
    on("#btn-open-carrier-modal", "click", openCarrierModal);
    on("#btn-close-carrier-modal", "click", closeCarrierModal);
    on("#btn-cancel-carrier-modal", "click", closeCarrierModal);
    on("#btn-open-user-modal", "click", openUserModal);
    on("#btn-close-user-modal", "click", closeUserModal);
    on("#btn-cancel-user-modal", "click", closeUserModal);
    on("#btn-save-user", "click", saveUser);
    on("#btn-open-role-modal", "click", openRoleModal);
    on("#btn-close-role-modal", "click", closeRoleModal);
    on("#btn-cancel-role-modal", "click", closeRoleModal);
    on("#btn-close-details-modal", "click", closeDetailsModal);
    on("#btn-close-items-modal", "click", closeItemsModal);
    on("#btn-add-item", "click", addItem);
    on("#btn-close-carrier-details-modal", "click", closeCarrierDetailsModal);
    on("#btn-close-amount-modal", "click", closeAmountModal);
    on("#btn-cancel-amount-modal", "click", closeAmountModal);
    on("#btn-open-abono-modal", "click", openAbonoModal);
    on("#btn-close-abono-modal", "click", closeAbonoModal);
    on("#btn-cancel-abono-modal", "click", closeAbonoModal);
    on("#abono-form", "submit", saveAbono);
    on("#btn-close-movements-modal", "click", closeMovementsModal);
    on("#btn-close-gestion", "click", closeGestion);
    on("#btn-liquidar-deuda", "click", liquidarDeudaTransportista);

    on("#tab-carrier-pendientes", "click", () => switchCarrierTab("pendientes"));
    on("#tab-carrier-historial", "click", () => switchCarrierTab("historial"));

    on("#btn-carrier-pagos-prev", "click", () => {
        if (carrierPagosCurrentPage > 1) {
            cargarHistorialPagosCarrier(carrierPagosCurrentPage - 1);
        }
    });
    on("#btn-carrier-pagos-next", "click", () => {
        if (carrierPagosCurrentPage < carrierPagosLastPage) {
            cargarHistorialPagosCarrier(carrierPagosCurrentPage + 1);
        }
    });

    on("#btn-close-pago-detalle-modal", "click", () => {
        const modal = $("#pago-detalle-modal");
        if (modal) {
            modal.classList.remove("is-active");
            modal.setAttribute("aria-hidden", "true");
        }
    });
    $("#pago-detalle-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "pago-detalle-modal") {
            const modal = $("#pago-detalle-modal");
            modal.classList.remove("is-active");
            modal.setAttribute("aria-hidden", "true");
        }
    });

    on("#btn-export", "click", exportEnvios);
    on("#btn-clear-filters", "click", clearFilters);
    $("#envio-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "envio-modal") closeModal();
    });
    $("#client-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "client-modal") closeClientModal();
    });
    $("#carrier-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "carrier-modal") closeCarrierModal();
    });
    $("#user-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "user-modal") closeUserModal();
    });
    $("#role-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "role-modal") closeRoleModal();
    });
    $("#amount-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "amount-modal") closeAmountModal();
    });
    $("#abono-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "abono-modal") closeAbonoModal();
    });
    $("#movements-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "movements-modal") closeMovementsModal();
    });
    $("#details-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "details-modal") closeDetailsModal();
    });
    $("#items-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "items-modal") closeItemsModal();
    });
    $("#carrier-details-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "carrier-details-modal") closeCarrierDetailsModal();
    });
    

    $("#search-input").addEventListener("input", debounce((event) => setFilter("q", event.target.value.trim()), 250));
    $("#filter-pago").addEventListener("change", (event) => setFilter("pago", event.target.value));
    $("#filter-fecha-desde").addEventListener("change", (event) => setFilter("fecha_desde", event.target.value));
    $("#filter-fecha-hasta").addEventListener("change", (event) => setFilter("fecha_hasta", event.target.value));

    $("#btn-toggle-filters").addEventListener("click", () => {
        const panel = $("#filter-panel");
        const btn = $("#btn-toggle-filters");
        const open = panel.classList.toggle("open");
        btn.setAttribute("aria-expanded", String(open));
    });

    $("#debt-client-search")?.addEventListener("input", debounce((event) => filterDebtClients(event.target.value.trim()), 250));

    $("#client-search")?.addEventListener("input", debounce((event) => {
        loadClients(1, event.target.value.trim());
    }, 250));

    $("#carrier-search")?.addEventListener("input", debounce((event) => {
        loadCarriers(1, event.target.value.trim());
    }, 250));

    $("#envio-form").addEventListener("submit", saveEnvio);
    on("#btn-save-client", "click", saveClient);
    on("#btn-show-client-form", "click", showClientForm);
    on("#client-admin-form", "submit", saveAdminClient);
    on("#carrier-admin-form", "submit", saveAdminCarrier);
    on("#user-form", "submit", saveUser);
    on("#role-form", "submit", saveRole);
    on("#amount-form", "submit", saveAmount);
    on("#role-search-input", "input", debounce((event) => loadRoles(event.target.value.trim()), 250));
    on("#f-guia", "input", (event) => {
        event.target.value = event.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, "");
    });

    document.addEventListener("click", (event) => {
        if (!event.target.closest(".field-relative")) {
            closeDropdowns();
        }
    });
}

function showSection(section) {
    const titles = {
        envios: ["Envíos", "Registro y control de carga"],
        clientes: ["Clientes", "Registro y consulta de clientes"],
        transportistas: ["Transportistas", "Responsables de traslado"],
        dashboard: ["Dashboard", "Indicadores y control operativo"],
        roles: ["Roles", "Permisos por tipo de usuario"],
        usuarios: ["Usuarios", "Cuentas de acceso al sistema"],
        montos: ["Montos", "Envios pendientes de liquidacion"],
        cuentas: ["Cuentas por cobrar", "Control de deudas y abonos"],
        auditoria: ["Auditoría Financiera", "Control y trazabilidad de ingresos y egresos"],
        maquinarias: ["Maquinarias", "Registro y disponibilidad de unidades"],
        alquileres: ["Alquileres", "Gestión de contratos y servicios de maquinaria"]
    };

    document.querySelectorAll("[data-page]").forEach((page) => {
        page.classList.toggle("hidden", page.dataset.page !== section);
    });
    document.querySelectorAll("[data-section]").forEach((button) => {
        button.classList.toggle("active", button.dataset.section === section);
    });

    const activeButton = document.querySelector(`.sidebar-link[data-section="${section}"]`);
    const parentGroup = activeButton?.closest(".sidebar-group");
    if (parentGroup?.classList.contains("collapsed")) {
        parentGroup.classList.remove("collapsed");
        const savedState = JSON.parse(localStorage.getItem("sidebarGroups") || "{}");
        savedState[parentGroup.dataset.group] = true;
        localStorage.setItem("sidebarGroups", JSON.stringify(savedState));
    }

    const [title, subtitle] = titles[section] || titles.envios;
    if ($("#section-title")) $("#section-title").textContent = title;
    if ($("#section-subtitle")) $("#section-subtitle").textContent = subtitle;
    closeSidebarMenu();

    try {
        if (section === "clientes" || section === "transportistas") {
            loadManagementLists();
            if (section === "transportistas") {
                closeGestion();
            }
        } else if (section === "roles") {
            loadRoles();
        } else if (section === "montos") {
            loadPendingAmounts();
        } else if (section === "cuentas") {
            loadDebtClients();
        } else if (section === "auditoria") {
            initAuditoriaFinanciera();
        } else if (section === "dashboard") {
            loadDashboardGanancias();
        } else if (section === "usuarios") {
            loadUsers();
        } else if (section === "maquinarias") {
            loadMaquinarias();
        } else if (section === "alquileres") {
            loadAlquileres();
        }
    } catch (err) {
        console.error(`Error al activar sección ${section}:`, err);
    }
}

function toggleSidebarMenu() {
    const sidebar = $(".sidebar");
    const backdrop = $("#sidebar-backdrop");
    const toggles = document.querySelectorAll(".sidebar-toggle");
    const isOpen = sidebar?.classList.toggle("open") ?? false;

    backdrop?.classList.toggle("open", isOpen);
    toggles.forEach((btn) => btn.setAttribute("aria-expanded", String(isOpen)));
}

function closeSidebarMenu() {
    const sidebar = $(".sidebar");
    const backdrop = $("#sidebar-backdrop");
    const toggles = document.querySelectorAll(".sidebar-toggle");

    sidebar?.classList.remove("open");
    backdrop?.classList.remove("open");
    toggles.forEach((btn) => btn.setAttribute("aria-expanded", "false"));
}

function initSidebarGroups() {
    const savedState = JSON.parse(localStorage.getItem("sidebarGroups") || "{}");
    const activeSection = document.querySelector(".sidebar-link.active")?.dataset.section;
    const activeGroup = activeSection ? document.querySelector(`.sidebar-group [data-section="${activeSection}"]`)?.closest(".sidebar-group")?.dataset.group : null;

    document.querySelectorAll(".sidebar-group").forEach((group) => {
        const groupName = group.dataset.group;
        const isCollapsed = savedState[groupName] === false;

        if (isCollapsed && groupName !== activeGroup) {
            group.classList.add("collapsed");
        }
    });
}

function toggleSidebarGroup(groupName) {
    const group = document.querySelector(`.sidebar-group[data-group="${groupName}"]`);
    if (!group) return;

    group.classList.toggle("collapsed");

    const savedState = JSON.parse(localStorage.getItem("sidebarGroups") || "{}");
    savedState[groupName] = !group.classList.contains("collapsed");
    localStorage.setItem("sidebarGroups", JSON.stringify(savedState));
}

function setFilter(key, value) {
    state.filters[key] = value;
    state.pagination.page = 1;
    loadEnvios();
}

async function loadEnvios() {
    const params = filterParams();
    params.set("page", state.pagination.page);
    params.set("per_page", 15);

    try {
        const response = await api.get(`/envios?${params.toString()}`);
        state.envios = response.data;
        state.nextCode = response.next_code;
        state.pagination.lastPage = response.meta.last_page;
        state.pagination.total = response.meta.total;
        renderTable();
        renderPagination();
        loadStats();
    } catch (error) {
        showToast(error.message, "error");
        renderTable();
    }
}

async function loadStats() {
    try {
        const response = await api.get("/stats");
        $("#stat-total").textContent = response.data.total;
        $("#stat-hoy").textContent = response.data.hoy;
        $("#stat-ce").textContent = response.data.contra_entrega;
        if ($("#stat-pendientes")) $("#stat-pendientes").textContent = response.data.pendientes_monto;
    } catch {
        // stats fail silently
    }
}

function renderTable() {
    const tbody = $("#envios-tbody");

    if (!state.envios.length) {
        tbody.innerHTML = `<tr><td class="empty-state" colspan="${can("envios.amounts") ? 7 : 6}">No hay envios registrados.</td></tr>`;
        return;
    }

    tbody.innerHTML = state.envios.map((envio) => {
        const badgeClass = envio.pago === "Pagado" ? "pagado" : envio.pago === "Contra Entrega" ? "contra" : "credito";
        return `
            <tr>
                <td class="cell-code">${escapeHtml(envio.codigo)}</td>
                <td class="cell-muted">${formatDate(envio.fecha)}</td>
                <td>
                    <strong>${escapeHtml(envio.cliente)}</strong>
                </td>
                <td class="col-hide-mob cell-code">${escapeHtml(envio.guia || "-")}</td>
                <td class="col-hide-mob"><span class="badge ${badgeClass}">${escapeHtml(envio.pago)}</span></td>
                ${can("envios.amounts") ? `<td class="col-hide-mob cell-code">${money(envio.monto)}</td>` : ""}
                <td>
                    <div class="row-actions">
                        <span class="material-icon" data-items="${envio.id}" title="Ver items" role="button" tabindex="0" aria-label="Ver items">inventory_2</span>
                        <span class="material-icon" data-details="${envio.id}" title="Ver detalles" role="button" tabindex="0" aria-label="Ver detalles">info</span>
                        ${can("envios.amounts") ? `<span class="material-icon" data-amount="${envio.id}" title="Registrar monto" role="button" tabindex="0" aria-label="Registrar monto">payments</span>` : ""}
                        ${can("envios.update") ? `<span class="material-icon" data-edit="${envio.id}" title="Editar" role="button" tabindex="0" aria-label="Editar">edit</span>` : ""}
                        ${can("envios.delete") ? `<span class="material-icon delete" data-delete="${envio.id}" title="Eliminar" role="button" tabindex="0" aria-label="Eliminar">delete</span>` : ""}
                    </div>
                </td>
            </tr>
        `;
    }).join("");

    tbody.querySelectorAll("[data-items]").forEach((button) => {
        button.addEventListener("click", () => showItems(Number(button.dataset.items)));
    });
    tbody.querySelectorAll("[data-edit]").forEach((button) => {
        button.addEventListener("click", () => editEnvio(Number(button.dataset.edit)));
    });
    tbody.querySelectorAll("[data-details]").forEach((button) => {
        button.addEventListener("click", () => showDetails(Number(button.dataset.details)));
    });
    tbody.querySelectorAll("[data-amount]").forEach((button) => {
        button.addEventListener("click", () => openAmountModal(Number(button.dataset.amount)));
    });
    tbody.querySelectorAll("[data-delete]").forEach((button) => {
        button.addEventListener("click", () => deleteEnvio(Number(button.dataset.delete)));
    });
}

function renderPagination() {
    const container = $("#pagination-controls");
    if (!container) return;

    const { page, lastPage, total } = state.pagination;
    if (lastPage <= 1) {
        container.innerHTML = "";
        return;
    }

    let html = '<div class="pagination">';
    html += `<button class="pagination-btn" data-page="1" ${page === 1 ? "disabled" : ""}>&laquo;</button>`;
    html += `<button class="pagination-btn" data-page="${page - 1}" ${page <= 1 ? "disabled" : ""}>&lsaquo;</button>`;

    const start = Math.max(1, page - 2);
    const end = Math.min(lastPage, page + 2);

    if (start > 1) {
        html += `<button class="pagination-btn" data-page="1">1</button>`;
        if (start > 2) html += `<span class="pagination-ellipsis">&hellip;</span>`;
    }

    for (let i = start; i <= end; i++) {
        html += `<button class="pagination-btn${i === page ? " active" : ""}" data-page="${i}">${i}</button>`;
    }

    if (end < lastPage) {
        if (end < lastPage - 1) html += `<span class="pagination-ellipsis">&hellip;</span>`;
        html += `<button class="pagination-btn" data-page="${lastPage}">${lastPage}</button>`;
    }

    html += `<button class="pagination-btn" data-page="${page + 1}" ${page >= lastPage ? "disabled" : ""}>&rsaquo;</button>`;
    html += `<button class="pagination-btn" data-page="${lastPage}" ${page === lastPage ? "disabled" : ""}>&raquo;</button>`;
    html += "</div>";

    container.innerHTML = html;
    container.querySelectorAll(".pagination-btn:not([disabled])").forEach((btn) => {
        btn.addEventListener("click", () => goToPage(Number(btn.dataset.page)));
    });
}

function goToPage(page) {
    if (page < 1 || page > state.pagination.lastPage) return;
    state.pagination.page = page;
    loadEnvios();
    $("#envios-tbody").scrollIntoView({ block: "start", behavior: "smooth" });
}

function openModal() {
    resetForm();
    $("#f-codigo").value = state.nextCode;
    state.items = [{ cantidad: "", descripcion: "" }];
    renderItems();
    $("#envio-modal").classList.add("open");
    $("#envio-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeModal() {
    $("#envio-modal").classList.remove("open");
    $("#envio-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    state.editingId = null;
    closeDropdowns();
}

function openClientModal() {
    $("#client-admin-form").reset();
    $("#client-modal-title").textContent = "Registrar cliente";
    state.editingClienteId = null;
    $("#client-modal").classList.add("open");
    $("#client-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeClientModal() {
    $("#client-modal").classList.remove("open");
    $("#client-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    state.editingClienteId = null;
}

function openCarrierModal() {
    $("#carrier-admin-form").reset();
    document.querySelectorAll('input[name="carrier-tipo"]').forEach((cb) => { cb.checked = false; });
    $("#carrier-modal-title").textContent = "Registrar transportista";
    state.editingCarrierId = null;
    $("#carrier-modal").classList.add("open");
    $("#carrier-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeCarrierModal() {
    $("#carrier-modal").classList.remove("open");
    $("#carrier-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    state.editingCarrierId = null;
}

function closeDetailsModal() {
    $("#details-modal").classList.remove("open");
    $("#details-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
}

function closeRoleModal() {
    $("#role-modal").classList.remove("open");
    $("#role-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    state.editingRoleId = null;
}

function closeAmountModal() {
    $("#amount-modal").classList.remove("open");
    $("#amount-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    state.liquidatingEnvioId = null;
}

function openAbonoModal() {
    $("#abono-form").reset();
    $("#abono-cliente-id").value = "";
    $("#abono-cliente-nombre").value = "";
   const inputAbonoFecha = $("#abono-fecha");
    if (inputAbonoFecha && !inputAbonoFecha.value) {
        inputAbonoFecha.value = new Date().toISOString().slice(0, 10);
    }
    $("#abono-summary").innerHTML = '<small>Selecciona un cliente de la lista para registrar un abono.</small>';
    $("#abono-modal").classList.add("open");
    $("#abono-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function openAbonoModalForClient(id, dni, nombre, saldoTotal) {
    const clienteData = state.debtClients.find(c => c.id === id);

    currentAbonoClientData = {
        id: id,
        nombre: nombre,
        deudaEnvios: Number(clienteData?.deuda_envios ?? saldoTotal),
        deudaAlquileres: Number(clienteData?.deuda_alquileres ?? 0),
        deudaTotal: Number(saldoTotal)
    };

    $("#abono-cliente-id").value = id;
    $("#abono-card-nombre").textContent = `${nombre} ${dni ? `(${dni})` : ''}`;
    const inputAbonoFecha = $("#abono-fecha");
    if (inputAbonoFecha && !inputAbonoFecha.value) {
        inputAbonoFecha.value = new Date().toISOString().slice(0, 10);
    }
    $("#abono-monto").value = '';
    $("#abono-observacion").value = '';
    if ($("#abono-metodo-pago")) $("#abono-metodo-pago").value = "Efectivo";

    $("#abono-sub-envios").textContent = money(currentAbonoClientData.deudaEnvios);
    $("#abono-sub-alquileres").textContent = money(currentAbonoClientData.deudaAlquileres);

    const selectOrigen = $("#abono-origen");
    if (selectOrigen) {
        if (currentAbonoClientData.deudaEnvios <= 0 && currentAbonoClientData.deudaAlquileres > 0) {
            selectOrigen.value = 'alquileres';
        } else {
            selectOrigen.value = 'envios';
        }
    }

    actualizarCardSaldosAbono();

    $("#abono-modal").classList.add("open");
    $("#abono-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeAbonoModal() {
    $("#abono-modal").classList.remove("open");
    $("#abono-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    state.selectedDebtClient = null;
}

async function saveAbono(event) {
    event.preventDefault();

    const payload = {
        cliente_id: parseInt($("#abono-cliente-id").value, 10),
        origen: $("#abono-origen").value,
        monto: $("#abono-monto").value,
        fecha: $("#abono-fecha").value,
        metodo_pago: $("#abono-metodo-pago").value,
        observacion: $("#abono-observacion").value.trim() || null,
    };

    try {
        await api.post("/cuentas-corrientes/abono", payload);
        closeAbonoModal();
        showToast("Abono registrado correctamente.");
        loadDebtClients();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function loadDebtClients() {
    if (!$("#debt-clients-list")) return;

    try {
        const response = await api.get("/cuentas-corrientes/clientes-con-deuda");
        state.debtClients = response.data;
        renderDebtClients();
    } catch (error) {
        showToast(error.message, "error");
    }
}

function renderDebtClients() {
    const list = $("#debt-clients-list");
    if (!list) return;

    const totalDeuda = state.debtClients.reduce((sum, c) => sum + Number(c.saldo_pendiente || 0), 0);
    $("#debt-client-count").textContent = state.debtClients.length;
    $("#debt-total-amount").textContent = money(totalDeuda);

    if (!state.debtClients.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="5">No hay clientes con deuda pendiente.</td></tr>`;
        return;
    }

    list.innerHTML = state.debtClients.map((cliente) => {
        let docDisplay = cliente.dni ? escapeHtml(cliente.dni) : "-";
        return `
        <tr>
            <td class="cell-code">${docDisplay}</td>
            <td><strong>${escapeHtml(cliente.nombre)}</strong></td>
            <td class="cell-muted">${escapeHtml(cliente.telefono || "-")}</td>
            <td class="cell-code" style="color: #f59e0b;">${money(cliente.saldo_pendiente)}</td>
            <td>
                <div class="row-actions">
                    <button class="button button-primary button-with-icon" type="button" data-abono-id="${cliente.id}" data-abono-dni="${escapeHtml(cliente.dni || "")}" data-abono-nombre="${escapeHtml(cliente.nombre)}" data-abono-saldo="${cliente.saldo_pendiente}"><span class="material-icon">payments</span>Abonar</button>
                    <button class="button button-with-icon" type="button" data-movements="${cliente.id}" title="Ver movimientos"><span class="material-icon">receipt_long</span>Movimientos</button>
                </div>
            </td>
        </tr>`;
    }).join("");

    list.querySelectorAll("[data-abono-id]").forEach((button) => {
        button.addEventListener("click", () => {
            openAbonoModalForClient(
                parseInt(button.dataset.abonoId, 10),
                button.dataset.abonoDni || null,
                button.dataset.abonoNombre,
                Number(button.dataset.abonoSaldo)
            );
        });
    });

    list.querySelectorAll("[data-movements]").forEach((button) => {
        button.addEventListener("click", () => openMovementsModal(parseInt(button.dataset.movements, 10)));
    });
}

function filterDebtClients(search) {
    const list = $("#debt-clients-list");
    if (!list || !search) {
        renderDebtClients();
        return;
    }

    const filtered = state.debtClients.filter((c) =>
        c.nombre.toLowerCase().includes(search.toLowerCase()) ||
        (c.dni && c.dni.includes(search))
    );

    const totalDeuda = filtered.reduce((sum, c) => sum + Number(c.saldo_pendiente || 0), 0);
    $("#debt-client-count").textContent = filtered.length;
    $("#debt-total-amount").textContent = money(totalDeuda);

    if (!filtered.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="5">No se encontraron clientes.</td></tr>`;
        return;
    }

    list.innerHTML = filtered.map((cliente) => {
        let docDisplay = cliente.dni ? escapeHtml(cliente.dni) : "-";
        return `
        <tr>
            <td class="cell-code">${docDisplay}</td>
            <td><strong>${escapeHtml(cliente.nombre)}</strong></td>
            <td class="cell-muted">${escapeHtml(cliente.telefono || "-")}</td>
            <td class="cell-code" style="color: #f59e0b;">${money(cliente.saldo_pendiente)}</td>
            <td>
                <div class="row-actions">
                    <button class="button button-primary button-with-icon" type="button" data-abono-id="${cliente.id}" data-abono-dni="${escapeHtml(cliente.dni || "")}" data-abono-nombre="${escapeHtml(cliente.nombre)}" data-abono-saldo="${cliente.saldo_pendiente}"><span class="material-icon">payments</span>Abonar</button>
                    <button class="button button-with-icon" type="button" data-movements="${cliente.id}" title="Ver movimientos"><span class="material-icon">receipt_long</span>Movimientos</button>
                </div>
            </td>
        </tr>`;
    }).join("");

    list.querySelectorAll("[data-abono-id]").forEach((button) => {
        button.addEventListener("click", () => {
            openAbonoModalForClient(
                parseInt(button.dataset.abonoId, 10),
                button.dataset.abonoDni || null,
                button.dataset.abonoNombre,
                Number(button.dataset.abonoSaldo)
            );
        });
    });

    list.querySelectorAll("[data-movements]").forEach((button) => {
        button.addEventListener("click", () => openMovementsModal(parseInt(button.dataset.movements, 10)));
    });
}

async function openMovementsModal(id) {
    const cliente = state.debtClients.find((c) => c.id === id);
    $("#movements-modal-title").textContent = cliente ? `Movimientos de ${cliente.nombre}` : "Movimientos del cliente";
    
    $("#movements-summary").innerHTML = cliente
        ? `
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div>
                    <strong style="font-size: 1.05rem;">${escapeHtml(cliente.nombre)}</strong>
                    <div style="color: var(--muted); font-size: 0.8rem; margin-top: 2px;">
                        ${cliente.dni ? "Doc: " + escapeHtml(cliente.dni) + " · " : ""}Tel: ${escapeHtml(cliente.telefono || "-")}
                    </div>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.8rem; color: var(--muted); display: block;">Deuda Total Consolidada</span>
                    <strong style="color: #f59e0b; font-size: 1.25rem; font-family: 'Outfit', sans-serif;">${money(cliente.saldo_pendiente)}</strong>
                </div>
            </div>
          `
        : "";

    $("#movements-modal-list").innerHTML = `<tr><td class="empty-state" colspan="6">Cargando movimientos...</td></tr>`;
    
    currentMovementTab = 'todos';
    const btnTodos = $("#tab-mov-todos");
    if (btnTodos) {
        btnTodos.style.background = 'var(--accent)';
        btnTodos.style.color = '#000';
        btnTodos.style.fontWeight = '700';
    }

    $("#movements-modal").classList.add("open");
    $("#movements-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";

    try {
        const response = await api.get(`/cuentas-corrientes?cliente_id=${id}`);
        state.debtMovements = response.data;
        renderDebtMovementsInModal();
    } catch (error) {
        showToast(error.message, "error");
        $("#movements-modal-list").innerHTML = `<tr><td class="empty-state" colspan="6">Error al cargar movimientos.</td></tr>`;
    }
}

function closeMovementsModal() {
    $("#movements-modal").classList.remove("open");
    $("#movements-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
}

function renderDebtMovementsInModal() {
    const list = $("#movements-modal-list");
    if (!list) return;

    let movimientos = state.debtMovements || [];

    if (currentMovementTab === 'envios') {
        movimientos = movimientos.filter(m => m.envio_id !== null || (m.observacion && m.observacion.includes('[ENVIOS]')));
    } else if (currentMovementTab === 'alquileres') {
        movimientos = movimientos.filter(m => m.alquiler_id !== null || (m.observacion && m.observacion.includes('[ALQUILERES]')));
    }

    if (!movimientos.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="6" style="padding: 2rem; text-align: center; color: var(--muted);">No hay movimientos en esta categoría.</td></tr>`;
        return;
    }

    list.innerHTML = movimientos.map((mov) => {
        const tipoClass = mov.tipo === "cargo" ? "contra" : "pagado";
        const tipoLabel = mov.tipo === "cargo" ? "Cargo" : "Abono";

        let origenBadge = '-';
        if (mov.envio_codigo) {
            origenBadge = `<span class="badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);">Envío ${escapeHtml(mov.envio_codigo)}</span>`;
        } else if (mov.alquiler_id) {
            const placa = mov.alquiler_placa ? ` · ${escapeHtml(mov.alquiler_placa)}` : '';
            origenBadge = `<span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Alq. #${mov.alquiler_id}${placa}</span>`;
        } else if (mov.tipo === 'abono') {
            if (mov.observacion && mov.observacion.includes('[ALQUILERES]')) {
                origenBadge = `<span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">Abono Máquina</span>`;
            } else {
                origenBadge = `<span class="badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">Abono Envío</span>`;
            }
        }

        const montoDisplay = mov.tipo === "cargo" 
            ? `<span style="color: var(--danger); font-weight: 700;">+ ${money(mov.monto)}</span>`
            : `<span style="color: var(--success); font-weight: 700;">- ${money(mov.monto)}</span>`;

        return `
            <tr>
                <td class="cell-muted">${formatDate(mov.fecha)}</td>
                <td><span class="badge ${tipoClass}">${tipoLabel}</span></td>
                <td>${origenBadge}</td>
                <td class="cell-code">${montoDisplay}</td>
                <td class="cell-code" style="font-weight: 600;">${money(mov.saldo_acumulado)}</td>
                <td class="cell-muted" style="font-size: 0.82rem;">${escapeHtml(mov.observacion || "-")}</td>
            </tr>
        `;
    }).join("");
}

function resetForm() {
    $("#envio-form").reset();
    state.editingId = null;
    state.items = [{ cantidad: "", descripcion: "" }];
    $("#envio-db-id").value = "";
    $("#f-cliente-id").value = "";
    $("#f-dni").value = "";
    $("#f-nombre").value = "";
    $("#f-telefono").value = "";
    $("#f-direccion").value = "";
    $("#modal-title").textContent = "Registrar envio";
    document.querySelectorAll(".field-error").forEach((item) => item.classList.remove("field-error"));
    $("#dni-status").textContent = "";
    hideClientForm();
    hideClientNotFound();
    setToday();
}

function setToday() {
    const today = new Date();
    const iso = today.toISOString().slice(0, 10);
    const inputFecha = $("#f-fecha");
    
    // Solo asignamos la fecha de hoy si el campo está vacío
    if (inputFecha && !inputFecha.value) {
        inputFecha.value = iso;
    }
    
    const labelCurrent = $("#current-date");
    if (labelCurrent) {
        labelCurrent.textContent = today.toLocaleDateString("es-PE", {
            weekday: "short",
            day: "numeric",
            month: "short",
            year: "numeric",
        });
    }
}

async function saveClient() {
    const payload = clientPayload();
    const error = validateClientPayload(payload);
    if (error) {
        showToast(error, "error");
        return;
    }

    try {
        const response = await api.post("/clientes", payload);
        fillClient(response.data);
        hideClientForm();
        hideClientNotFound();
        $("#dni-status").textContent = "Cliente guardado.";
        showToast("Cliente guardado correctamente.");
        loadManagementLists();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function saveAdminClient(event) {
    event.preventDefault();

    const payload = {
        tipo_documento: $("#client-admin-tipo-documento").value,
        dni: $("#client-admin-dni").value.trim(),
        nombre: $("#client-admin-name").value.trim(),
        telefono: $("#client-admin-phone").value.trim(),
        direccion: $("#client-admin-address").value.trim(),
    };

    const error = validateClientPayload(payload);
    if (error) {
        showToast(error, "error");
        return;
    }

    try {
        if (state.editingClienteId) {
            await api.put(`/clientes/${state.editingClienteId}`, payload);
            showToast("Cliente actualizado correctamente.");
        } else {
            await api.post("/clientes", payload);
            showToast("Cliente guardado correctamente.");
        }
        $("#client-admin-tipo-documento").value = "";
        ["#client-admin-dni", "#client-admin-name", "#client-admin-phone", "#client-admin-address"].forEach((selector) => {
            $(selector).value = "";
        });
        closeClientModal();
        loadManagementLists();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function editCliente(id) {
    try {
        const response = await api.get(`/clientes/${id}`);
        const cliente = response.data;
        openClientModal();
        state.editingClienteId = cliente.id;
        $("#client-modal-title").textContent = "Editar cliente";
        $("#client-admin-tipo-documento").value = cliente.tipo_documento || "";
        $("#client-admin-dni").value = cliente.dni || "";
        $("#client-admin-name").value = cliente.nombre || "";
        $("#client-admin-phone").value = cliente.telefono || "";
        $("#client-admin-address").value = cliente.direccion || "";
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function deleteCliente(id) {
    const confirmed = await showConfirmModal("¿Está seguro de eliminar este cliente? El historial de envíos se mantendrá.");
    if (!confirmed) return;

    try {
        await api.delete(`/clientes/${id}`);
        showToast("Cliente eliminado correctamente.");
        loadManagementLists();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function toggleClienteActive(id) {
    try {
        const response = await api.patch(`/clientes/${id}/toggle`);
        const cliente = response.data;
        showToast(`Cliente ${cliente.activo ? "activado" : "desactivado"}.`);
        loadManagementLists();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function saveAdminCarrier(event) {
    event.preventDefault();

    const tipos = Array.from(document.querySelectorAll('input[name="carrier-tipo"]:checked')).map((cb) => cb.value);

    const payload = {
        nombre: $("#carrier-admin-name").value.trim(),
        tipo_documento: $("#carrier-admin-tipo-documento").value,
        documento: $("#carrier-admin-document").value.trim(),
        telefono: $("#carrier-admin-phone").value.trim(),
        tipos: tipos.length ? tipos : null,
    };

    try {
        if (state.editingCarrierId) {
            await api.put(`/transportistas/${state.editingCarrierId}`, payload);
            showToast("Transportista actualizado correctamente.");
        } else {
            await api.post("/transportistas", payload);
            showToast("Transportista guardado correctamente.");
        }
        $("#carrier-admin-tipo-documento").value = "";
        ["#carrier-admin-name", "#carrier-admin-document", "#carrier-admin-phone"].forEach((selector) => {
            $(selector).value = "";
        });
        document.querySelectorAll('input[name="carrier-tipo"]').forEach((cb) => { cb.checked = false; });
        closeCarrierModal();
        loadManagementLists();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function editCarrier(id) {
    try {
        const response = await api.get(`/transportistas/${id}`);
        const carrier = response.data;
        openCarrierModal();
        state.editingCarrierId = carrier.id;
        $("#carrier-modal-title").textContent = "Editar transportista";
        $("#carrier-admin-tipo-documento").value = carrier.tipo_documento || "";
        $("#carrier-admin-name").value = carrier.nombre || "";
        $("#carrier-admin-document").value = carrier.documento || "";
        $("#carrier-admin-phone").value = carrier.telefono || "";
        const carrierTipos = carrier.tipos || [];
        document.querySelectorAll('input[name="carrier-tipo"]').forEach((cb) => {
            cb.checked = carrierTipos.includes(cb.value);
        });
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function showCarrierDetails(id) {
    try {
        const response = await api.get(`/transportistas/${id}`);
        const carrier = response.data;
        const tipoLabels = { encomiendas: "Encomiendas", maquinaria: "Maquinaria", camion: "Camión" };
        const tipos = carrier.tipos || [];
        const tiposDisplay = tipos.length
            ? tipos.map((t) => `<span class="tipo-badge tipo-badge-${t}">${tipoLabels[t] || t}</span>`).join(" ")
            : `<span class="cell-muted">-</span>`;
        const tipoDocDisplay = carrier.tipo_documento
            ? `<span class="badge ${carrier.tipo_documento === "ruc" ? "contra" : "pagado"}">${carrier.tipo_documento.toUpperCase()}</span>`
            : `<span class="cell-muted">-</span>`;
        const docDisplay = carrier.documento
            ? `<span class="cell-code">${escapeHtml(carrier.documento)}</span>`
            : `<span class="cell-muted">-</span>`;
        const estadoDisplay = `<span class="badge ${carrier.activo ? "pagado" : "contra"}">${carrier.activo ? "Activo" : "Inactivo"}</span>`;

        $("#carrier-details-title").textContent = `Detalle: ${carrier.nombre}`;
        $("#carrier-details-body").innerHTML = `
            <div class="detail-grid">
                <div><span>Nombre</span><strong>${escapeHtml(carrier.nombre)}</strong></div>
                <div><span>Tipo documento</span><strong>${tipoDocDisplay}</strong></div>
                <div><span>Documento</span><strong>${docDisplay}</strong></div>
                <div><span>Teléfono</span><strong>${escapeHtml(carrier.telefono || "-")}</strong></div>
                <div><span>Especialidad</span><strong>${tiposDisplay}</strong></div>
                <div><span>Estado</span><strong>${estadoDisplay}</strong></div>
            </div>
        `;
        $("#carrier-details-modal").classList.add("open");
        $("#carrier-details-modal").setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
    } catch (error) {
        showToast(error.message, "error");
    }
}

function closeCarrierDetailsModal() {
    $("#carrier-details-modal").classList.remove("open");
    $("#carrier-details-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
}

async function deleteCarrier(id) {
    const confirmed = await showConfirmModal("¿Está seguro de eliminar este transportista? El historial de envíos se mantendrá.");
    if (!confirmed) return;

    try {
        await api.delete(`/transportistas/${id}`);
        showToast("Transportista eliminado correctamente.");
        loadManagementLists();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function toggleCarrierActive(id) {
    try {
        const response = await api.patch(`/transportistas/${id}/toggle`);
        const carrier = response.data;
        showToast(`Transportista ${carrier.activo ? "activado" : "desactivado"}.`);
        loadManagementLists();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function openGestion(transportistaId) {
    state.gestionTransportistaId = transportistaId;

    try {
        const [carrierResponse, enviosResponse] = await Promise.all([
            api.get(`/transportistas/${transportistaId}`),
            api.get(`/transportistas/${transportistaId}/envios`),
        ]);

        const carrier = carrierResponse.data;
        state.gestionEnvios = enviosResponse.data;

        $("#gestion-carrier-name").textContent = carrier.nombre;
        $("#gestion-carrier-info").textContent = `${carrier.documento || "Sin documento"} · ${carrier.telefono || "Sin teléfono"}`;

        const totalEnvios = state.gestionEnvios.length;
        const deudaTotal = state.gestionEnvios
            .filter(e => !e.pagado_transportista_at)
            .reduce((sum, e) => sum + Number(e.costo_pendiente ?? e.costo_transportista ?? 0), 0);
        const liquidados = state.gestionEnvios.filter(e => Boolean(e.pagado_transportista_at)).length;

        $("#gestion-envios-total").textContent = totalEnvios;
        $("#gestion-deuda-total").textContent = money(deudaTotal);
        $("#gestion-liquidados").textContent = liquidados;

        const btnLiquidar = $("#btn-liquidar-deuda");
        if (btnLiquidar) {
            btnLiquidar.disabled = deudaTotal <= 0;
            btnLiquidar.style.opacity = deudaTotal <= 0 ? "0.5" : "1";
            btnLiquidar.style.cursor = deudaTotal <= 0 ? "not-allowed" : "pointer";
        }

        renderGestionEnvios();

        $("#carrier-list-view").classList.add("hidden");
        $("#carrier-gestion-view").classList.remove("hidden");
        switchCarrierTab("pendientes");
        window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (error) {
        showToast(error.message, "error");
    }
}

function renderGestionEnvios() {
    const list = $("#gestion-envios-list");
    if (!list) return;

    const envios = state.gestionEnvios || [];
    const estados = ["Registrado", "En camino", "Entregado", "Devuelto"];
    const enviosPendientes = envios.filter((e) => !e.pagado_transportista_at);

    if (!enviosPendientes.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="7" style="text-align:center; padding: 2.5rem 1rem; color: #94a3b8;">No hay envíos pendientes de abono para este transportista.</td></tr>`;
        return;
    }

    list.innerHTML = enviosPendientes.map((envio) => {
        const estadoOptions = estados.map(e => `<option value="${e}" ${e === envio.estado ? "selected" : ""}>${e}</option>`).join("");
        const liquidado = envio.liquidado_at ? `<span class="badge pagado">Sí</span>` : `<span class="badge contra">No</span>`;
        const itemsSummary = envio.items ? envio.items.map(i => `${i.cantidad}x ${escapeHtml(i.descripcion)}`).join('<br>') : '-';
        const displayCosto = Number(envio.costo_pendiente ?? envio.costo_transportista ?? 0);

        return `
            <tr>
                <td class="cell-code">${escapeHtml(envio.codigo)}</td>
                <td class="cell-muted">${formatDate(envio.fecha)}</td>
                <td class="cell-muted" style="font-size: 0.8rem;">${itemsSummary}</td>
                <td class="cell-code">
                    ${money(displayCosto)}
                    ${envio.costo_pendiente < envio.costo_transportista ? `<br><small style="color: #94a3b8; font-size: 0.75rem;">Total orig.: ${money(envio.costo_transportista)}</small>` : ""}
                </td>
                <td>
                    <select class="gestion-estado-select" data-envio-id="${envio.id}" style="padding: 0.25rem; font-size: 0.85rem; border-radius: 4px; border: 1px solid #475569; background: #1e293b; color: #e2e8f0;">
                        ${estadoOptions}
                    </select>
                </td>
                <td>${liquidado}</td>
                <td>
                    <div class="row-actions">
                        ${(envio.liquidado_at || envio.costo_pendiente > 0) ? `<button class="button button-primary" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;" data-gestion-pagar="${envio.id}" type="button">Pagar</button>` : `<span class="badge contra">No liquidado</span>`}                    
                    </div>
                </td>
            </tr>
        `;
    }).join("");

    list.querySelectorAll(".gestion-estado-select").forEach((select) => {
        select.addEventListener("change", async (e) => {
            try {
                await api.patch(`/envios/${select.dataset.envioId}/estado`, { estado: e.target.value });
                showToast(`Estado actualizado a "${e.target.value}".`);
            } catch (error) {
                showToast(error.message, "error");
                openGestion(state.gestionTransportistaId);
            }
        });
    });

    list.querySelectorAll("[data-gestion-pagar]").forEach((button) => {
        button.addEventListener("click", async () => {
            try {
                await api.patch(`/envios/${button.dataset.gestionPagar}/pagar-transportista`, {
                    transportista_id: state.gestionTransportistaId
                });
                showToast("Pago registrado con éxito.");
                openGestion(state.gestionTransportistaId);
            } catch (error) {
                showToast(error.message, "error");
            }
        });
    });
}

let carrierPagosCurrentPage = 1;
let carrierPagosLastPage = 1;

function switchCarrierTab(tab) {
    const btnPendientes = $("#tab-carrier-pendientes");
    const btnHistorial = $("#tab-carrier-historial");
    const contentPendientes = $("#carrier-tab-content-pendientes");
    const contentHistorial = $("#carrier-tab-content-historial");

    if (!btnPendientes || !btnHistorial) return;

    if (tab === "pendientes") {
        btnPendientes.classList.add("active");
        btnPendientes.style.color = "#f8fafc";
        btnPendientes.style.borderBottom = "2px solid #3b82f6";
        btnPendientes.style.fontWeight = "600";

        btnHistorial.classList.remove("active");
        btnHistorial.style.color = "#94a3b8";
        btnHistorial.style.borderBottom = "none";
        btnHistorial.style.fontWeight = "500";

        contentPendientes?.classList.remove("hidden");
        contentHistorial?.classList.add("hidden");
    } else {
        btnHistorial.classList.add("active");
        btnHistorial.style.color = "#f8fafc";
        btnHistorial.style.borderBottom = "2px solid #3b82f6";
        btnHistorial.style.fontWeight = "600";

        btnPendientes.classList.remove("active");
        btnPendientes.style.color = "#94a3b8";
        btnPendientes.style.borderBottom = "none";
        btnPendientes.style.fontWeight = "500";

        contentPendientes?.classList.add("hidden");
        contentHistorial?.classList.remove("hidden");

        cargarHistorialPagosCarrier(1);
    }
}

async function cargarHistorialPagosCarrier(page = 1) {
    if (!state.gestionTransportistaId) return;

    const list = $("#gestion-pagos-historial-list");
    if (!list) return;

    list.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 2rem; color: #94a3b8;">Cargando historial de pagos...</td></tr>`;

    try {
        const res = await fetch(`/api/transportistas/${state.gestionTransportistaId}/historial-pagos?page=${page}`, {
            headers: {
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            }
        });
        const json = await res.json();

        if (!json.ok) throw new Error(json.message || "Error al cargar historial");

        const paginacion = json.data;
        carrierPagosCurrentPage = paginacion.current_page;
        carrierPagosLastPage = paginacion.last_page;

        const totalText = $("#carrier-pagos-total-text");
        if (totalText) {
            totalText.textContent = `Página ${paginacion.current_page} de ${paginacion.last_page || 1} (${paginacion.total} pagos registrados)`;
        }

        const btnPrev = $("#btn-carrier-pagos-prev");
        const btnNext = $("#btn-carrier-pagos-next");
        if (btnPrev) btnPrev.disabled = !paginacion.prev_page_url;
        if (btnNext) btnNext.disabled = !paginacion.next_page_url;

        if (!paginacion.data.length) {
            list.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 2rem; color: #94a3b8;">No se registran pagos previos para este transportista.</td></tr>`;
            return;
        }

        list.innerHTML = paginacion.data.map(pago => {
            const fechaStr = new Date(pago.fecha_pago).toLocaleString("es-PE", {
                dateStyle: "short",
                timeStyle: "short"
            });
            const totalItems = pago.items ? pago.items.length : 0;
            const usuarioStr = pago.user ? pago.user.name : "Sistema";
            const obsStr = pago.observacion ? pago.observacion : "-";

            return `
                <tr>
                    <td><strong style="color: #38bdf8;">#${String(pago.id).padStart(5, '0')}</strong></td>
                    <td>${fechaStr}</td>
                    <td><strong style="color: #10b981;">${money(pago.monto_total)}</strong></td>
                    <td>${totalItems} bultos / ítems</td>
                    <td style="color: #cbd5e1; font-size: 0.85rem;">${obsStr}</td>
                    <td><span style="color: #94a3b8; font-size: 0.85rem;">${usuarioStr}</span></td>
                    <td style="text-align: right;">
                        <button type="button" class="button button-small btn-ver-pago-items" data-pago='${JSON.stringify(pago).replace(/'/g, "&apos;")}'>
                            Ver detalle
                        </button>
                    </td>
                </tr>
            `;
        }).join("");

        list.querySelectorAll(".btn-ver-pago-items").forEach(btn => {
            btn.addEventListener("click", () => {
                const pago = JSON.parse(btn.getAttribute("data-pago"));
                abrirModalDetallePago(pago);
            });
        });

    } catch (error) {
        list.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 2rem; color: #ef4444;">${error.message}</td></tr>`;
    }
}

function abrirModalDetallePago(pago) {
    const modal = $("#pago-detalle-modal");
    if (!modal) return;

    $("#pago-detalle-title").textContent = `Recibo de Pago #${String(pago.id).padStart(5, '0')}`;
    $("#pago-detalle-sub").textContent = new Date(pago.fecha_pago).toLocaleString("es-PE", {
        dateStyle: "full",
        timeStyle: "medium"
    });
    $("#pago-modal-monto").textContent = money(pago.monto_total);
    $("#pago-modal-user").textContent = pago.user ? pago.user.name : "Sistema";

    const tbody = $("#pago-detalle-items-list");
    if (tbody) {
        if (!pago.items || !pago.items.length) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 1rem;">No hay desglose de ítems para este registro.</td></tr>`;
        } else {
            tbody.innerHTML = pago.items.map(item => {
                const guiaStr = item.envio ? (item.envio.guia || item.envio.codigo) : "S/G";
                const clienteStr = item.envio?.cliente ? item.envio.cliente.nombre : "-";
                return `
                    <tr>
                        <td><strong style="color: #38bdf8;">${guiaStr}</strong></td>
                        <td>${clienteStr}</td>
                        <td>${item.cantidad}x ${item.descripcion}</td>
                        <td style="text-align: right; color: #10b981; font-weight: 600;">${money(item.costo_transportista)}</td>
                    </tr>
                `;
            }).join("");
        }
    }

    modal.classList.add("is-active");
    modal.setAttribute("aria-hidden", "false");
}

function closeGestion() {
    $("#carrier-gestion-view").classList.add("hidden");
    $("#carrier-list-view").classList.remove("hidden");
    state.gestionTransportistaId = null;
    state.gestionEnvios = [];
    switchCarrierTab("pendientes");

    if (typeof loadManagementLists === "function") {
        loadManagementLists();
    }
}

async function liquidarDeudaTransportista() {
    if (!state.gestionTransportistaId) return;

    const pendientes = (state.gestionEnvios || []).filter(e => !e.pagado_transportista_at && Number(e.costo_pendiente ?? e.costo_transportista ?? 0) > 0);    
    if (!pendientes.length) {
        showToast("No hay envíos pendientes de liquidar.");
        return;
    }

    const total = pendientes.reduce((sum, e) => sum + Number(e.costo_pendiente ?? e.costo_transportista ?? 0), 0);
    const confirmed = await showConfirmModal(`¿Liquidar ${pendientes.length} envío(s) por un total de ${money(total)}?`);
    if (!confirmed) return;

    try {
        const response = await api.post("/envios/pagar-transportista-lote", { 
            transportista_id: state.gestionTransportistaId,
            observacion: "Liquidación total de fletes"
        });
        const itemsCount = response.data?.items_pagados ?? pendientes.length;
        showToast(`Pago registrado exitosamente (${itemsCount} ítem(s) abonados).`);
        openGestion(state.gestionTransportistaId);
        loadManagementLists();
    } catch (error) {
        showToast(error.message, "error");
    }
}

let transportistasCache = [];

const loadTransportistasForLookup = async () => {
    try {
        const response = await fetch('/api/transportistas');
        const data = await response.json();
        transportistasCache = Array.isArray(data) ? data : (data.data || []);
    } catch (e) {
        console.error('Error cargando transportistas', e);
    }
};

function initTransportistaSearch() {
    const inputTrans = $("#f-transportista-search");
    const dropdownTrans = $("#transportista-dropdown");
    const statusTrans = $("#transportista-status");

    if (!inputTrans) return;

    inputTrans.addEventListener("input", (e) => {
        const query = e.target.value.toLowerCase().trim();
        if (query.length < 1) {
            dropdownTrans.innerHTML = "";
            dropdownTrans.style.display = "none";
            return;
        }

        const filtered = transportistasCache.filter(t => 
            t.nombre.toLowerCase().includes(query) || 
            (t.documento && t.documento.toLowerCase().includes(query))
        ).slice(0, 5);

        if (filtered.length === 0) {
            dropdownTrans.innerHTML = '<div class="dropdown-item" style="padding: 8px; color: #94a3b8;">No se encontraron transportistas</div>';
            dropdownTrans.style.display = "block";
            return;
        }

        dropdownTrans.innerHTML = filtered.map(t => `
            <div class="dropdown-item" data-id="${t.id}" data-nombre="${t.nombre}" style="padding: 8px; cursor: pointer; border-bottom: 1px solid var(--line);">
                <strong style="color: var(--text);">${t.nombre}</strong><br>
                <small style="color: var(--muted);">${t.documento || 'Sin doc'} - ${t.telefono || ''}</small>
            </div>
        `).join("");
        dropdownTrans.style.display = "block";
    });

    dropdownTrans.addEventListener("click", (e) => {
        const item = e.target.closest(".dropdown-item");
        if (!item) return;
        
        const transportistaId = item.getAttribute("data-id");
        const transportistaNombre = item.getAttribute("data-nombre");

        if (!transportistaId) return;

        $("#f-transportista-id").value = transportistaId;
        $("#f-transportista-search").value = transportistaNombre;
        
        if (statusTrans) {
            statusTrans.textContent = `Seleccionado: ${transportistaNombre}`;
        }
        
        dropdownTrans.style.display = "none";
        dropdownTrans.innerHTML = "";
    });

    document.addEventListener("click", (e) => {
        if (!inputTrans.contains(e.target) && !dropdownTrans.contains(e.target)) {
            dropdownTrans.style.display = "none";
        }
    });
}

async function searchClient() {
    const input = $("#f-cliente-search");
    const dropdown = $("#client-dropdown");
    const search = input.value.trim();

    if (search.length < 2) {
        hideClientNotFound();
        hideClientForm();
        clearSelectedClient(false);
        $("#dni-status").textContent = "";
        dropdown.classList.remove("open");
        dropdown.innerHTML = "";
        return;
    }

    if (search && search !== $("#f-nombre").value.trim() && search !== $("#f-dni").value.trim()) {
        clearSelectedClient(false);
    }

    try {
        const response = await api.get(`/clientes?search=${encodeURIComponent(search)}`);
        const resultadosLimitados = response.data.slice(0, 5);

        if (!resultadosLimitados.length) {
            dropdown.classList.remove("open");
            clearSelectedClient(false);
            showClientNotFound(search);
            return;
        }

        hideClientNotFound();

        dropdown.innerHTML = resultadosLimitados.map((cliente) => `
            <button type="button" data-id="${cliente.id}" data-dni="${escapeHtml(cliente.dni || "")}" data-tipo="${escapeHtml(cliente.tipo_documento || "")}" data-nombre="${escapeHtml(cliente.nombre)}" data-telefono="${escapeHtml(cliente.telefono || "")}" data-direccion="${escapeHtml(cliente.direccion || "")}">
                <span>${escapeHtml(cliente.nombre)}</span>
                <small>${cliente.dni ? (cliente.tipo_documento ? cliente.tipo_documento.toUpperCase() + ": " : "") + escapeHtml(cliente.dni) : "Sin documento"}</small>
            </button>
        `).join("");
        dropdown.classList.add("open");

        dropdown.querySelectorAll("button").forEach((button) => {
            button.addEventListener("click", () => {
                fillClient({
                    id: parseInt(button.dataset.id, 10),
                    dni: button.dataset.dni || null,
                    tipo_documento: button.dataset.tipo || null,
                    nombre: button.dataset.nombre,
                    telefono: button.dataset.telefono || null,
                    direccion: button.dataset.direccion || null,
                });
                dropdown.classList.remove("open");
            });
        });
    } catch (error) {
        showToast(error.message, "error");
    }
}

function fillClient(cliente) {
    $("#f-cliente-id").value = cliente.id;
    $("#f-dni").value = cliente.dni || "";
    $("#f-nombre").value = cliente.nombre;
    $("#f-telefono").value = cliente.telefono || "";
    $("#f-direccion").value = cliente.direccion || "";
    $("#f-cliente-search").value = cliente.nombre;
    $("#dni-status").textContent = "Cliente cargado.";
    hideClientNotFound();
    hideClientForm();
}

function clearSelectedClient(clearSearch = true) {
    $("#f-cliente-id").value = "";
    $("#f-dni").value = "";
    $("#f-nombre").value = "";
    $("#f-telefono").value = "";
    $("#f-direccion").value = "";
    if (clearSearch) {
        $("#f-cliente-search").value = "";
    }
}

function showClientNotFound(search) {
    const looksLikeDni = /^\d{8}$/.test(search);
    const looksLikeRuc = /^\d{11}$/.test(search);
    if (looksLikeDni) {
        $("#client-inline-tipo-documento").value = "dni";
        $("#client-inline-dni").value = search;
    } else if (looksLikeRuc) {
        $("#client-inline-tipo-documento").value = "ruc";
        $("#client-inline-dni").value = search;
    } else {
        $("#client-inline-tipo-documento").value = "";
        $("#client-inline-dni").value = "";
    }
    $("#client-inline-name").value = (looksLikeDni || looksLikeRuc) ? "" : search;
    $("#client-not-found").classList.remove("hidden");
    $("#btn-show-client-form").classList.remove("hidden");
    $("#dni-status").textContent = "";
}

function hideClientNotFound() {
    $("#client-not-found").classList.add("hidden");
    $("#btn-show-client-form").classList.add("hidden");
}

function showClientForm() {
    $("#client-inline-form").classList.remove("hidden");
    $("#client-inline-name").focus();
}

function hideClientForm() {
    $("#client-inline-form").classList.add("hidden");
    $("#client-inline-tipo-documento").value = "";
    ["#client-inline-dni", "#client-inline-name", "#client-inline-phone", "#client-inline-address"].forEach((selector) => {
        $(selector).value = "";
    });
}

async function saveEnvio(event) {
    event.preventDefault();

    const payload = envioPayload();
    if (!validate(payload)) {
        showToast("Completa los campos requeridos.", "error");
        return;
    }

    try {
        if (state.editingId) {
            await api.put(`/envios/${state.editingId}`, payload);
            showToast("Envio actualizado correctamente.");
        } else {
            await api.post("/envios", payload);
            showToast("Envio registrado correctamente.");
        }
        closeModal();
        loadEnvios();
        if ($("#pending-amounts-list")) loadPendingAmounts();
    } catch (error) {
        showToast(error.message, "error");
    }
}

function editEnvio(id) {
    const envio = state.envios.find((item) => item.id === id);
    if (!envio) return;

    openModal();
    state.editingId = id;
    $("#modal-title").textContent = "Editar envio";
    $("#envio-db-id").value = envio.id;
    $("#f-codigo").value = envio.codigo;
    $("#f-fecha").value = envio.fecha;
    $("#f-cliente-id").value = envio.cliente_id;
    $("#f-dni").value = envio.cliente_dni;
    $("#f-nombre").value = envio.cliente;
    $("#f-cliente-search").value = envio.cliente;
    $("#f-telefono").value = envio.telefono || "";
    $("#f-direccion").value = envio.direccion || "";
    if ($("#f-guia")) $("#f-guia").value = envio.guia || "";
    if ($("#f-pago")) $("#f-pago").value = envio.pago;
    if ($("#f-monto")) $("#f-monto").value = envio.monto || "";
    $("#f-obs").value = envio.observacion || "";
    state.items = envio.items && envio.items.length ? envio.items.map(i => ({ cantidad: String(i.cantidad), descripcion: i.descripcion })) : [{ cantidad: "", descripcion: "" }];
    renderItems();
}

async function deleteEnvio(id) {
    if (!confirm("Eliminar este envio?")) return;

    try {
        await api.delete(`/envios/${id}`);
        showToast("Envio eliminado.");
        loadEnvios();
    } catch (error) {
        showToast(error.message, "error");
    }
}

function showDetails(id) {
    const envio = state.envios.find((item) => item.id === id);
    if (!envio) return;

    $("#details-title").textContent = `Detalle ${envio.codigo}`;
    let docLabel = "Documento";
    let docValue = "-";
    if (envio.cliente_dni) {
        const tipoDoc = envio.cliente_tipo_documento;
        docLabel = tipoDoc ? tipoDoc.toUpperCase() : "Documento";
        docValue = escapeHtml(envio.cliente_dni);
    }

    const hasMontos = envio.items && envio.items.some(i => i.monto > 0);
    const itemsTotal = envio.items ? envio.items.reduce((sum, i) => sum + (parseFloat(i.monto) || 0), 0) : 0;
    const montoHeader = hasMontos ? '<th class="item-monto-cell">Monto</th>' : '';
    const itemsHtml = envio.items && envio.items.length ? envio.items.map((item) => `
        <tr>
            <td class="item-cantidad">${item.cantidad}</td>
            <td>${escapeHtml(item.descripcion)}</td>
            ${hasMontos ? `<td class="item-monto-cell">${money(item.monto || 0)}</td>` : ''}
        </tr>
    `).join('') : `<tr><td colspan="${hasMontos ? 3 : 2}" class="empty-state">Sin items</td></tr>`;
    const itemsFooter = hasMontos ? `<tfoot><tr><td colspan="2" class="item-monto-cell">Total</td><td class="item-monto-cell">${money(itemsTotal)}</td></tr></tfoot>` : '';

    $("#details-body").innerHTML = `
        <div class="detail-grid">
            <div><span>Fecha</span><strong>${formatDate(envio.fecha)}</strong></div>
            <div><span>Cliente</span><strong>${escapeHtml(envio.cliente)}</strong></div>
            <div><span>${docLabel}</span><strong>${docValue}</strong></div>
            <div><span>Telefono</span><strong>${escapeHtml(envio.telefono || "-")}</strong></div>
            <div><span>Direccion</span><strong>${escapeHtml(envio.direccion || "-")}</strong></div>
            <div><span>Guia</span><strong>${escapeHtml(envio.guia || "-")}</strong></div>
            <div><span>Pago</span><strong>${escapeHtml(envio.pago)}</strong></div>
            <div class="span-all"><span>Observacion</span><p>${escapeHtml(envio.observacion || "-")}</p></div>
            <div class="span-all">
                <span>Items</span>
                <div class="items-table">
                    <table>
                        <thead><tr><th>Cant.</th><th>Descripción</th>${montoHeader}</tr></thead>
                        <tbody>${itemsHtml}</tbody>
                        ${itemsFooter}
                    </table>
                </div>
            </div>
        </div>
    `;
    $("#details-modal").classList.add("open");
    $("#details-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function slug(value) {
    return String(value || "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-|-$/g, "");
}

function slugRole(value) {
    return slug(value).replace(/-/g, "_");
}

function addItem() {
    state.items.push({ cantidad: "", descripcion: "" });
    renderItems();
}

function removeItem(index) {
    if (state.items.length <= 1) return;
    state.items.splice(index, 1);
    renderItems();
}

function renderItems() {
    const container = $("#items-container");
    if (!container) return;

    container.innerHTML = state.items.map((item, index) => `
        <tr class="item-table-row" data-item-index="${index}">
            <td class="item-table-index">${index + 1}</td>
            <td>
                <input 
                    type="text" 
                    class="item-cantidad" 
                    value="${escapeHtml(item.cantidad)}" 
                    inputmode="numeric" 
                    placeholder="1"
                    autocomplete="off"
                >
            </td>
            <td>
                <input 
                    type="text" 
                    class="item-descripcion" 
                    value="${escapeHtml(item.descripcion)}" 
                    placeholder="Ej: cajas pequeñas de mostaza de sachet"
                    autocomplete="off"
                >
            </td>
            <td style="text-align: center;">
                <button 
                    type="button" 
                    class="btn-item-delete" 
                    data-remove-item="${index}" 
                    title="Quitar ítem"
                    ${state.items.length <= 1 ? 'disabled style="opacity: 0.2; cursor: not-allowed;"' : ''}
                >
                    <span class="material-icon delete">delete</span>
                </button>
            </td>
        </tr>
    `).join('');

    container.querySelectorAll('.item-cantidad').forEach((input, i) => {
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            state.items[i].cantidad = e.target.value;
        });
    });

    container.querySelectorAll('.item-descripcion').forEach((input, i) => {
        input.addEventListener('input', (e) => {
            state.items[i].descripcion = e.target.value;
        });
    });

    container.querySelectorAll('[data-remove-item]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const index = Number(e.currentTarget.dataset.removeItem);
            removeItem(index);
        });
    });
}

function showItems(id) {
    const envio = state.envios.find((item) => item.id === id);
    if (!envio || !envio.items) return;

    const hasMontos = envio.items.some(i => i.monto > 0);
    const itemsTotal = envio.items.reduce((sum, i) => sum + (parseFloat(i.monto) || 0), 0);
    const montoHeader = hasMontos ? '<th class="item-monto-cell">Monto</th>' : '';
    const itemsFooter = hasMontos ? `<tfoot><tr><td colspan="2" class="item-monto-cell">Total</td><td class="item-monto-cell">${money(itemsTotal)}</td></tr></tfoot>` : '';

    $("#items-title").textContent = `Items - ${envio.codigo}`;
    const thead = $("#items-modal thead tr");
    if (thead) thead.innerHTML = `<th>Cantidad</th><th>Descripción</th>${montoHeader}`;

    $("#items-list").innerHTML = envio.items.length ? envio.items.map((item) => `
        <tr>
            <td class="item-cantidad">${item.cantidad}</td>
            <td>${escapeHtml(item.descripcion)}</td>
            ${hasMontos ? `<td class="item-monto-cell">${money(item.monto || 0)}</td>` : ''}
        </tr>
    `).join('') : `<tr><td colspan="${hasMontos ? 3 : 2}" class="empty-state">Sin items</td></tr>`;

    const existingTfoot = $("#items-modal table tfoot");
    if (existingTfoot) existingTfoot.remove();
    if (itemsFooter) $("#items-modal table").insertAdjacentHTML('beforeend', itemsFooter);

    $("#items-modal").classList.add("open");
    $("#items-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeItemsModal() {
    $("#items-modal").classList.remove("open");
    $("#items-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
}

function envioPayload() {
    return {
        codigo: $("#f-codigo").value,
        fecha: $("#f-fecha").value,
        cliente_id: parseInt($("#f-cliente-id").value, 10) || null,
        cliente: $("#f-nombre").value.trim(),
        transportista_id: parseInt($("#f-transportista-id").value, 10) || null,
        telefono: $("#f-telefono").value.trim(),
        direccion: $("#f-direccion").value.trim(),
        guia: $("#f-guia") ? $("#f-guia").value.trim() : null,
        observacion: $("#f-obs").value.trim(),
        items: state.items.filter(i => i.cantidad && i.descripcion.trim()).map(i => ({
            cantidad: parseInt(i.cantidad, 10),
            descripcion: i.descripcion.trim(),
        })),
    };
}

function clientPayload() {
    const tipoDocumento = $("#client-inline-tipo-documento").value;
    return {
        tipo_documento: tipoDocumento,
        dni: $("#client-inline-dni").value.trim(),
        nombre: $("#client-inline-name").value.trim(),
        telefono: $("#client-inline-phone").value.trim(),
        direccion: $("#client-inline-address").value.trim(),
    };
}

function validateClientPayload(payload) {
    if (payload.tipo_documento && !payload.dni) {
        return `El número de ${payload.tipo_documento.toUpperCase()} es requerido.`;
    }
    if (!payload.tipo_documento && payload.dni) {
        if (!/^\d+$/.test(payload.dni) || ![8, 11].includes(payload.dni.length)) {
            return "El documento debe tener 8 dígitos (DNI) o 11 dígitos (RUC).";
        }
    }
    if (payload.tipo_documento === "dni" && payload.dni) {
        if (!/^\d{8}$/.test(payload.dni)) {
            return "El DNI debe tener 8 dígitos numéricos.";
        }
    }
    if (payload.tipo_documento === "ruc" && payload.dni) {
        if (!/^\d{11}$/.test(payload.dni)) {
            return "El RUC debe tener 11 dígitos numéricos.";
        }
    }
    if (!payload.nombre) {
        return "El nombre del cliente es requerido.";
    }
    if (payload.telefono && !/^\d{9}$/.test(payload.telefono)) {
        return "El telefono debe tener 9 digitos numericos.";
    }
    return null;
}

function validate(payload) {
    let valid = true;
    const required = {
        "#f-cliente-search": payload.cliente_id && payload.cliente,
        "#f-transportista-search": payload.transportista_id,
    };

    if ($("#f-guia")) {
        required["#f-guia"] = payload.guia;
    }

    Object.entries(required).forEach(([selector, value]) => {
        const field = $(selector);
        const empty = !String(value || "").trim();
        field.classList.toggle("field-error", empty);
        if (empty) valid = false;
    });

    if (!payload.items || !payload.items.length) {
        showToast("Agrega al menos un item con cantidad y descripción.", "error");
        valid = false;
    }

    return valid;
}

function clearFilters() {
    state.filters = {
        q: "",
        pago: "",
        fecha_desde: "",
        fecha_hasta: "",
    };
    state.pagination.page = 1;
    $("#search-input").value = "";
    $("#filter-pago").value = "";
    $("#filter-fecha-desde").value = "";
    $("#filter-fecha-hasta").value = "";
    loadEnvios();
}

function exportEnvios() {
    window.location.href = `/api/envios/export?${filterParams().toString()}`;
}

let clientState = { page: 1, search: "" };
let carrierState = { page: 1, search: "" };

async function loadClients(page = 1, search = clientState.search) {
    clientState.page = page;
    clientState.search = search;
    const params = new URLSearchParams({ page, per_page: 15 });
    if (search) params.append("search", search);

    try {
        const response = await api.get(`/clientes?${params.toString()}`);
        renderClientsTable(response.data);
        renderAuditPagination("clients-pagination", response.meta, (newPage) => loadClients(newPage));
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function loadCarriers(page = 1, search = carrierState.search) {
    carrierState.page = page;
    carrierState.search = search;
    const params = new URLSearchParams({ page, per_page: 15 });
    if (search) params.append("search", search);

    try {
        const response = await api.get(`/transportistas?${params.toString()}`);
        let metrics = {};
        if (can("envios.amounts")) {
            try {
                const metricsResponse = await api.get("/transportistas/metrics");
                metrics = metricsResponse.data;
            } catch {
                // metrics fail silently
            }
        }
        renderCarriersTable(response.data, metrics);
        renderAuditPagination("carriers-pagination", response.meta, (newPage) => loadCarriers(newPage));
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function loadManagementLists() {
    await Promise.all([loadClients(1), loadCarriers(1)]);
}

function renderClientsTable(items) {
    const list = $("#clients-list");
    if (!items.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="7">Sin clientes registrados.</td></tr>`;
        return;
    }

    list.innerHTML = items.map((cliente) => {
        const tipoDisplay = cliente.tipo_documento
            ? `<span class="badge ${cliente.tipo_documento === "ruc" ? "contra" : "pagado"}">${cliente.tipo_documento.toUpperCase()}</span>`
            : "-";
        const docDisplay = cliente.dni
            ? `<span class="cell-code">${escapeHtml(cliente.dni)}</span>`
            : "-";
        const estadoDisplay = `<span class="badge ${cliente.activo ? "pagado" : "contra"}">${cliente.activo ? "Activo" : "Inactivo"}</span>`;
        const canManage = can("clientes.manage");
        const canDelete = can("clientes.delete");
        const actions = [];
        if (canManage) {
            actions.push(`<span class="material-icon" data-client-edit="${cliente.id}" title="Editar" role="button" tabindex="0" aria-label="Editar cliente">edit</span>`);
            actions.push(`<span class="material-icon" data-client-toggle="${cliente.id}" title="${cliente.activo ? "Desactivar" : "Activar"}" role="button" tabindex="0" aria-label="${cliente.activo ? "Desactivar cliente" : "Activar cliente"}">${cliente.activo ? "toggle_off" : "toggle_on"}</span>`);
        }
        if (canDelete) {
            actions.push(`<span class="material-icon delete" data-client-delete="${cliente.id}" title="Eliminar" role="button" tabindex="0" aria-label="Eliminar cliente">delete</span>`);
        }
        const actionsHtml = actions.length
            ? `<div class="row-actions">${actions.join("")}</div>`
            : "-";
        return `
        <tr>
            <td>${tipoDisplay}</td>
            <td>${docDisplay}</td>
            <td><strong>${escapeHtml(cliente.nombre)}</strong></td>
            <td class="cell-muted">${escapeHtml(cliente.telefono || "-")}</td>
            <td class="cell-muted">${escapeHtml(cliente.direccion || "-")}</td>
            <td>${estadoDisplay}</td>
            <td>${actionsHtml}</td>
        </tr>`;
    }).join("");

    list.querySelectorAll("[data-client-edit]").forEach((button) => {
        button.addEventListener("click", () => editCliente(Number(button.dataset.clientEdit)));
    });
    list.querySelectorAll("[data-client-toggle]").forEach((button) => {
        button.addEventListener("click", () => toggleClienteActive(Number(button.dataset.clientToggle)));
    });
    list.querySelectorAll("[data-client-delete]").forEach((button) => {
        button.addEventListener("click", () => deleteCliente(Number(button.dataset.clientDelete)));
    });
}

function renderCarriersTable(items, metrics = {}) {
    const list = $("#carriers-list");
    if (!items.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="${can("envios.amounts") ? 9 : 7}">Sin transportistas registrados.</td></tr>`;
        return;
    }

    const tipoLabels = { encomiendas: "Encomiendas", maquinaria: "Maquinaria", camion: "Camión" };
    const showMetrics = can("envios.amounts");

    list.innerHTML = items.map((transportista) => {
        const tipos = transportista.tipos || [];
        const tiposDisplay = tipos.length
            ? tipos.map((t) => `<span class="tipo-badge tipo-badge-${t}">${tipoLabels[t] || t}</span>`).join("")
            : `<span class="cell-muted">-</span>`;
        const tipoDocDisplay = transportista.tipo_documento
            ? `<span class="badge ${transportista.tipo_documento === "ruc" ? "contra" : "pagado"}">${transportista.tipo_documento.toUpperCase()}</span>`
            : `<span class="cell-muted">-</span>`;
        const docDisplay = transportista.documento
            ? `<span class="cell-code">${escapeHtml(transportista.documento)}</span>`
            : `<span class="cell-muted">-</span>`;
        const estadoDisplay = `<span class="badge ${transportista.activo ? "pagado" : "contra"}">${transportista.activo ? "Activo" : "Inactivo"}</span>`;
        const m = metrics[transportista.id] || {};
        const canManage = can("transportistas.manage");
        const canDelete = can("transportistas.delete");
        const actions = [];
        if (showMetrics) {
            actions.push(`<button class="button button-primary" style="font-size: 0.8rem; padding: 0.25rem 0.75rem;" data-carrier-gestion="${transportista.id}" type="button">Gestionar</button>`);
        }
        if (canManage) {
            actions.push(`<span class="material-icon" data-carrier-details="${transportista.id}" title="Ver detalles" role="button" tabindex="0" aria-label="Ver detalles">info</span>`);
            actions.push(`<span class="material-icon" data-carrier-edit="${transportista.id}" title="Editar" role="button" tabindex="0" aria-label="Editar transportista">edit</span>`);
            actions.push(`<span class="material-icon" data-carrier-toggle="${transportista.id}" title="${transportista.activo ? "Desactivar" : "Activar"}" role="button" tabindex="0" aria-label="${transportista.activo ? "Desactivar transportista" : "Activar transportista"}">${transportista.activo ? "toggle_off" : "toggle_on"}</span>`);
        }
        if (canDelete) {
            actions.push(`<span class="material-icon delete" data-carrier-delete="${transportista.id}" title="Eliminar" role="button" tabindex="0" aria-label="Eliminar transportista">delete</span>`);
        }
        const actionsHtml = actions.length
            ? `<div class="row-actions">${actions.join("")}</div>`
            : "-";
        const metricsCols = showMetrics
            ? `<td class="cell-code">${m.envios_total || 0}</td><td class="cell-code" style="color: ${(m.deuda_pendiente || 0) > 0 ? '#f59e0b' : '#10b981'}">${money(m.deuda_pendiente || 0)}</td>`
            : "";
        return `
        <tr>
            <td><strong>${escapeHtml(transportista.nombre)}</strong></td>
            <td>${tipoDocDisplay}</td>
            <td>${docDisplay}</td>
            <td>${tiposDisplay}</td>
            <td class="cell-muted">${escapeHtml(transportista.telefono || "-")}</td>
            <td>${estadoDisplay}</td>
            ${metricsCols}
            <td>${actionsHtml}</td>
        </tr>`;
    }).join("");

    list.querySelectorAll("[data-carrier-details]").forEach((button) => {
        button.addEventListener("click", () => showCarrierDetails(Number(button.dataset.carrierDetails)));
    });
    list.querySelectorAll("[data-carrier-edit]").forEach((button) => {
        button.addEventListener("click", () => editCarrier(Number(button.dataset.carrierEdit)));
    });
    list.querySelectorAll("[data-carrier-toggle]").forEach((button) => {
        button.addEventListener("click", () => toggleCarrierActive(Number(button.dataset.carrierToggle)));
    });
    list.querySelectorAll("[data-carrier-delete]").forEach((button) => {
        button.addEventListener("click", () => deleteCarrier(Number(button.dataset.carrierDelete)));
    });
    list.querySelectorAll("[data-carrier-gestion]").forEach((button) => {
        button.addEventListener("click", () => openGestion(Number(button.dataset.carrierGestion)));
    });
}

async function loadPendingAmounts() {
    if (!$("#pending-amounts-list")) return;

    try {
        const response = await api.get("/envios?pago=Pendiente&per_page=1000");
        state.pendingAmounts = response.data;
        renderPendingAmounts();
    } catch (error) {
        showToast(error.message, "error");
    }
}

function renderPendingAmounts() {
    const list = $("#pending-amounts-list");
    if (!list) return;

    if (!state.pendingAmounts.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="5">No hay envios pendientes de monto.</td></tr>`;
        return;
    }

    list.innerHTML = state.pendingAmounts.map((envio) => {
        const itemsCount = envio.items ? envio.items.length : 0;
        const itemsSummary = envio.items ? envio.items.map(i => `${i.cantidad}x`).join(', ') : '-';
        return `
            <tr>
                <td class="cell-code">${escapeHtml(envio.codigo)}</td>
                <td class="cell-muted">${formatDate(envio.fecha)}</td>
                <td><strong>${escapeHtml(envio.cliente)}</strong></td>
                <td>${itemsCount} item(s): ${escapeHtml(itemsSummary)}</td>
                <td>
                    <button class="button button-primary button-with-icon" type="button" data-pending-amount="${envio.id}"><span class="material-icon">payments</span>Liquidar</button>
                </td>
            </tr>
        `;
    }).join("");

    list.querySelectorAll("[data-pending-amount]").forEach((button) => {
        button.addEventListener("click", () => openAmountModal(Number(button.dataset.pendingAmount)));
    });
}

function openAmountModal(id) {
    const envio = state.envios.find((item) => item.id === id)
        || state.pendingAmounts.find((item) => item.id === id);
    if (!envio) return;

    state.liquidatingEnvioId = id;
    $("#amount-form").reset();
    $("#amount-envio-id").value = id;
    $("#amount-payment").value = envio.pago && envio.pago !== "Pendiente" ? envio.pago : "Credito";
    $("#amount-total").value = envio.monto && parseFloat(envio.monto) > 0 ? envio.monto : "";

    $("#amount-summary").innerHTML = `
       <strong>${escapeHtml(envio.codigo)} - ${escapeHtml(envio.cliente || "Cliente")}</strong>
    `;

    renderAmountItems(envio.items || []);

    $("#amount-modal").classList.add("open");
    $("#amount-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function renderAmountItems(items) {
    const container = $("#amount-items-container");
    const totalEl = $("#amount-items-total");
    if (!container || !items.length) {
        if (container) container.innerHTML = '<div style="color: var(--muted); font-size: 12px;">Sin items</div>';
        if (totalEl) totalEl.textContent = '';
        return;
    }

    container.innerHTML = items.map((item, index) => {
        const valorFlete = parseFloat(item.costo_transportista) || parseFloat(item.monto) || 0;
        const valorDisplay = valorFlete > 0 ? valorFlete.toFixed(2) : '';

        return `
            <div class="amount-item-row" data-item-id="${item.id || ''}">
                <div class="amount-item-info">
                    <strong>${escapeHtml(item.descripcion)}</strong>
                    <small>Cantidad: ${item.cantidad}</small>
                </div>
                <input type="text" class="amount-item-monto" value="${valorDisplay}" inputmode="decimal" placeholder="0.00" data-item-index="${index}">
            </div>
        `;
    }).join('');

    updateAmountItemsTotal();

    container.querySelectorAll('.amount-item-monto').forEach((input) => {
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9.]/g, '');
            updateAmountItemsTotal();
        });
    });
}

function updateAmountItemsTotal() {
    const inputs = document.querySelectorAll('#amount-items-container .amount-item-monto');
    let total = 0;
    inputs.forEach((input) => {
        total += parseFloat(input.value) || 0;
    });
    const totalEl = $("#amount-items-total");
    if (totalEl) {
        totalEl.textContent = total > 0 ? `Suma: ${money(total)}` : '';
    }
}

function getAmountItemsPayload() {
    const rows = document.querySelectorAll('#amount-items-container .amount-item-row');
    return Array.from(rows).map((row) => {
        const id = row.dataset.itemId;
        const input = row.querySelector('.amount-item-monto');
        return {
            id: id ? parseInt(id, 10) : null,
            monto: parseFloat(input.value) || 0,
        };
    });
}

async function saveAmount(event) {
    event.preventDefault();

    const id = state.liquidatingEnvioId || Number($("#amount-envio-id").value);
    const envio = state.envios.find((item) => item.id === id) || {};

    const payload = {
        monto: $("#amount-total").value,
        pago: $("#amount-payment").value,
        costo_transportista: envio.costo_transportista || null,
        items: getAmountItemsPayload(),
    };

    try {
        await api.put(`/envios/${id}/liquidacion`, payload);
        closeAmountModal();
        showToast("Monto guardado correctamente.");
        loadEnvios();
        if ($("#pending-amounts-list")) loadPendingAmounts();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function loadRoles(search = "") {
    if (!$("#roles-list")) return;

    try {
        const params = new URLSearchParams();
        if (search) params.set("search", search);
        const response = await api.get(`/roles?${params.toString()}`);
        state.roles = response.data;
        state.permissions = response.permissions;
        renderRolesTable();
        renderPermissionOptions();
    } catch (error) {
        showToast(error.message, "error");
    }
}

function renderRolesTable() {
    const list = $("#roles-list");
    if (!list) return;

    if (!state.roles.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="6">Sin roles registrados.</td></tr>`;
        return;
    }

    list.innerHTML = state.roles.map((role) => `
        <tr>
            <td class="cell-code">${role.id}</td>
            <td>
                <strong>${escapeHtml(role.label)}</strong>
                <span class="cell-sub">${escapeHtml(role.name)}</span>
            </td>
            <td>${permissionList(role.permissions)}</td>
            <td class="cell-muted">${escapeHtml(role.created_at || "-")}</td>
            <td>${role.users_count}</td>
            <td>
                <div class="row-actions">
                    <span class="material-icon" data-role-edit="${role.id}" title="Editar" role="button" tabindex="0" aria-label="Editar rol">edit</span>
                    <span class="material-icon delete" data-role-delete="${role.id}" title="Eliminar" role="button" tabindex="0" aria-label="Eliminar rol">delete</span>
                </div>
            </td>
        </tr>
    `).join("");

    list.querySelectorAll("[data-role-edit]").forEach((button) => {
        button.addEventListener("click", () => editRole(Number(button.dataset.roleEdit)));
    });
    list.querySelectorAll("[data-role-delete]").forEach((button) => {
        button.addEventListener("click", () => deleteRole(Number(button.dataset.roleDelete)));
    });
}

function permissionList(permissions) {
    if (!permissions.length) return '<span class="cell-muted">Sin permisos</span>';

    return `<ul class="permission-list">${permissions.map((permission) => (
        `<li>${escapeHtml(permission.name)}</li>`
    )).join("")}</ul>`;
}

function renderPermissionOptions(selected = []) {
    const box = $("#role-permissions");
    if (!box) return;

    const selectedIds = selected.map(Number);
    box.innerHTML = state.permissions.map((permission) => `
        <label class="permission-option ${permission.name === "envios.amounts" ? "permission-money" : ""}">
            <input type="checkbox" value="${permission.id}" ${selectedIds.includes(permission.id) ? "checked" : ""}>
            <span>
                <strong>${escapeHtml(permission.name)}</strong>
                <small>${escapeHtml(permission.label)}</small>
            </span>
        </label>
    `).join("");
}

function openRoleModal() {
    $("#role-form").reset();
    $("#role-id").value = "";
    state.editingRoleId = null;
    $("#role-modal-title").textContent = "Registrar rol";
    renderPermissionOptions();
    $("#role-modal").classList.add("open");
    $("#role-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function editRole(id) {
    const role = state.roles.find((item) => item.id === id);
    if (!role) return;

    openRoleModal();
    state.editingRoleId = id;
    $("#role-modal-title").textContent = "Editar rol";
    $("#role-id").value = role.id;
    $("#role-label").value = role.label;
    $("#role-name").value = role.name;
    renderPermissionOptions(role.permissions.map((permission) => permission.id));
}

async function saveRole(event) {
    event.preventDefault();

    const permissions = Array.from(document.querySelectorAll("#role-permissions input:checked"))
        .map((input) => Number(input.value));
    const payload = {
        label: $("#role-label").value.trim(),
        name: slugRole($("#role-name").value.trim() || $("#role-label").value.trim()),
        permissions,
    };

    try {
        if (state.editingRoleId) {
            await api.put(`/roles/${state.editingRoleId}`, payload);
            showToast("Rol actualizado correctamente.");
        } else {
            await api.post("/roles", payload);
            showToast("Rol registrado correctamente.");
        }
        closeRoleModal();
        loadRoles($("#role-search-input")?.value.trim() || "");
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function deleteRole(id) {
    if (!confirm("Eliminar este rol?")) return;

    try {
        await api.delete(`/roles/${id}`);
        showToast("Rol eliminado.");
        loadRoles($("#role-search-input")?.value.trim() || "");
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function loadUsers() {
    if (!$("#users-list")) return;

    try {
        const response = await api.get("/users");
        state.users = response.data;
        state.userRoles = response.roles;
        renderRoleOptions();
        renderUsersTable();
    } catch (error) {
        showToast(error.message, "error");
    }
}

function renderRoleOptions() {
    const select = $("#user-role");
    if (!select) return;

    select.innerHTML = state.userRoles.map((role) => (
        `<option value="${role.id}">${escapeHtml(role.label)}</option>`
    )).join("");
}

function renderUsersTable() {
    const list = $("#users-list");
    if (!list) return;

    if (!state.users.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="6">Sin usuarios registrados.</td></tr>`;
        return;
    }

    list.innerHTML = state.users.map((user) => `
        <tr>
            <td><strong>${escapeHtml(user.name)}</strong></td>
            <td class="cell-code">${escapeHtml(user.username)}</td>
            <td class="cell-muted">${escapeHtml(user.email)}</td>
            <td>${user.roles.map((role) => `<span class="badge credito">${escapeHtml(role.label)}</span>`).join(" ")}</td>
            <td><span class="badge ${user.active ? "pagado" : "contra"}">${user.active ? "Activo" : "Inactivo"}</span></td>
            <td>
                <div class="row-actions">
                    <span class="material-icon" data-user-edit="${user.id}" title="Editar" role="button" tabindex="0" aria-label="Editar usuario">edit</span>
                    <span class="material-icon delete" data-user-delete="${user.id}" title="Eliminar" role="button" tabindex="0" aria-label="Eliminar usuario">delete</span>
                </div>
            </td>
        </tr>
    `).join("");

    list.querySelectorAll("[data-user-edit]").forEach((button) => {
        button.addEventListener("click", () => editUser(Number(button.dataset.userEdit)));
    });
    list.querySelectorAll("[data-user-delete]").forEach((button) => {
        button.addEventListener("click", () => deleteUser(Number(button.dataset.userDelete)));
    });
}

function openUserModal() {
    $("#user-form").reset();
    $("#user-id").value = "";
    $("#user-active").checked = true;
    state.editingUserId = null;
    $("#user-modal-title").textContent = "Registrar usuario";
    renderRoleOptions();
    $("#user-modal").classList.add("open");
    $("#user-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeUserModal() {
    $("#user-modal").classList.remove("open");
    $("#user-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    state.editingUserId = null;
}

function editUser(id) {
    const user = state.users.find((item) => item.id === id);
    if (!user) return;

    openUserModal();
    state.editingUserId = id;
    $("#user-modal-title").textContent = "Editar usuario";
    $("#user-id").value = user.id;
    $("#user-name").value = user.name;
    $("#user-username").value = user.username;
    $("#user-email").value = user.email;
    $("#user-password").value = "";
    $("#user-active").checked = user.active;
    $("#user-role").value = user.roles[0]?.id || "";
}

async function saveUser(event) {
    event.preventDefault();

    const payload = {
        name: $("#user-name").value.trim(),
        username: $("#user-username").value.trim(),
        email: $("#user-email").value.trim(),
        password: $("#user-password").value,
        active: $("#user-active").checked,
        roles: [Number($("#user-role").value)],
    };

    try {
        if (state.editingUserId) {
            await api.put(`/users/${state.editingUserId}`, payload);
            showToast("Usuario actualizado correctamente.");
        } else {
            await api.post("/users", payload);
            showToast("Usuario registrado correctamente.");
        }
        closeUserModal();
        loadUsers();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function deleteUser(id) {
    if (!confirm("Eliminar este usuario?")) return;

    try {
        await api.delete(`/users/${id}`);
        showToast("Usuario eliminado.");
        loadUsers();
    } catch (error) {
        showToast(error.message, "error");
    }
}

/* ==========================================================================
   3. MÓDULO MAQUINARIAS
   ========================================================================== */
function bindMaquinariaEvents() {
    on("#btn-open-maquinaria-modal", "click", () => openMaquinariaModal());
    on("#btn-close-maquinaria-modal", "click", closeMaquinariaModal);
    on("#btn-cancel-maquinaria-modal", "click", closeMaquinariaModal);
    on("#maquinaria-form", "submit", saveMaquinaria);

    $("#maquinaria-search")?.addEventListener("input", debounce((e) => {
        const query = e.target.value.toLowerCase().trim();
        const filtered = state.maquinarias.filter(m => 
            m.placa.toLowerCase().includes(query) || 
            m.descripcion.toLowerCase().includes(query)
        );
        renderMaquinariasTable(filtered);
    }, 250));

    $("#maquinaria-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "maquinaria-modal") closeMaquinariaModal();
    });
}

async function loadMaquinarias() {
    if (!$("#maquinarias-list")) return;

    try {
        const response = await api.get("/maquinarias");
        state.maquinarias = response.data;
        renderMaquinariasTable(state.maquinarias);
    } catch (error) {
        showToast(error.message, "error");
    }
}

function renderMaquinariasTable(items) {
    const list = $("#maquinarias-list");
    if (!list) return;

    if (!items.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="5">No hay maquinarias registradas.</td></tr>`;
        return;
    }

    list.innerHTML = items.map((m) => `
        <tr>
            <td class="cell-code">${m.id}</td>
            <td><strong>${escapeHtml(m.placa)}</strong></td>
            <td>${escapeHtml(m.descripcion)}</td>
            <td><span class="badge ${m.activo ? "pagado" : "contra"}">${m.activo ? "Activo" : "Inactivo"}</span></td>
            <td style="text-align: right;">
                <div class="row-actions" style="justify-content: flex-end;">
                    <span class="material-icon" data-maquinaria-edit="${m.id}" title="Editar" role="button" tabindex="0" aria-label="Editar maquinaria">edit</span>
                    <span class="material-icon" data-maquinaria-toggle="${m.id}" title="${m.activo ? "Desactivar" : "Activar"}" role="button" tabindex="0" aria-label="${m.activo ? "Desactivar maquinaria" : "Activar maquinaria"}">${m.activo ? "toggle_off" : "toggle_on"}</span>
                </div>
            </td>
        </tr>
    `).join("");

    list.querySelectorAll("[data-maquinaria-edit]").forEach((button) => {
        button.addEventListener("click", () => editMaquinaria(Number(button.dataset.maquinariaEdit)));
    });
    list.querySelectorAll("[data-maquinaria-toggle]").forEach((button) => {
        button.addEventListener("click", () => toggleMaquinariaActive(Number(button.dataset.maquinariaToggle)));
    });
}

function openMaquinariaModal() {
    $("#maquinaria-form").reset();
    $("#maquinaria-id").value = "";
    state.editingMaquinariaId = null;
    $("#maquinaria-modal-title").textContent = "Registrar maquinaria";
    $("#maquinaria-modal").classList.add("open");
    $("#maquinaria-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeMaquinariaModal() {
    $("#maquinaria-modal").classList.remove("open");
    $("#maquinaria-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    state.editingMaquinariaId = null;
}

function editMaquinaria(id) {
    const item = state.maquinarias.find((m) => m.id === id);
    if (!item) return;

    openMaquinariaModal();
    state.editingMaquinariaId = id;
    $("#maquinaria-modal-title").textContent = "Editar maquinaria";
    $("#maquinaria-id").value = item.id;
    $("#maquinaria-placa").value = item.placa;
    $("#maquinaria-descripcion").value = item.descripcion;
}

async function saveMaquinaria(event) {
    event.preventDefault();

    const payload = {
        placa: $("#maquinaria-placa").value.trim().toUpperCase(),
        descripcion: $("#maquinaria-descripcion").value.trim(),
    };

    if (!payload.placa || !payload.descripcion) {
        showToast("Completa la placa y la descripción.", "error");
        return;
    }

    try {
        if (state.editingMaquinariaId) {
            await api.put(`/maquinarias/${state.editingMaquinariaId}`, payload);
            showToast("Maquinaria actualizada correctamente.");
        } else {
            await api.post("/maquinarias", payload);
            showToast("Maquinaria registrada correctamente.");
        }
        closeMaquinariaModal();
        loadMaquinarias();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function toggleMaquinariaActive(id) {
    try {
        const response = await api.patch(`/maquinarias/${id}/toggle`);
        const item = response.data;
        showToast(`Maquinaria ${item.activo ? "activada" : "desactivada"}.`);
        loadMaquinarias();
    } catch (error) {
        showToast(error.message, "error");
    }
}

/* ==========================================================================
   4. MÓDULO ALQUILERES
   ========================================================================== */
function bindAlquilerEvents() {
    on("#btn-open-alquiler-modal", "click", openAlquilerModal);
    on("#btn-close-alquiler-modal", "click", closeAlquilerModal);
    on("#btn-cancel-alquiler", "click", closeAlquilerModal);
    on("#btn-close-alquiler-details", "click", closeAlquilerDetailsModal);
    on("#alquiler-form", "submit", saveAlquiler);

    $("#alquiler-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "alquiler-modal") closeAlquilerModal();
    });
    $("#alquiler-details-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "alquiler-details-modal") closeAlquilerDetailsModal();
    });

    on("#alquiler-tiempo", "input", calcularPreviewAlquiler);
    on("#alquiler-precio", "input", calcularPreviewAlquiler);
    on("#alquiler-estado-pago", "change", toggleMetodoPagoAlquiler);

    // Filtros de Alquileres
    $("#btn-toggle-alquiler-filters")?.addEventListener("click", () => {
        const panel = $("#alquiler-filter-panel");
        const btn = $("#btn-toggle-alquiler-filters");
        const open = panel.classList.toggle("open");
        btn.setAttribute("aria-expanded", String(open));
    });

    $("#btn-clear-alquiler-filters")?.addEventListener("click", clearAlquilerFilters);
    $("#alquiler-search-input")?.addEventListener("input", debounce((e) => {
        state.alquilerFilters.q = e.target.value.trim();
        loadAlquileres();
    }, 250));
    $("#filter-alquiler-desde")?.addEventListener("change", (e) => {
        state.alquilerFilters.fecha_desde = e.target.value;
        loadAlquileres();
    });
    $("#filter-alquiler-hasta")?.addEventListener("change", (e) => {
        state.alquilerFilters.fecha_hasta = e.target.value;
        loadAlquileres();
    });
    $("#filter-alquiler-pago")?.addEventListener("change", (e) => {
        state.alquilerFilters.pago = e.target.value;
        loadAlquileres();
    });

    initAlquilerAutocompleteMaquinaria();
    initAlquilerAutocompleteCliente();
}

function clearAlquilerFilters() {
    state.alquilerFilters = { q: "", pago: "", fecha_desde: "", fecha_hasta: "" };
    if ($("#alquiler-search-input")) $("#alquiler-search-input").value = "";
    if ($("#filter-alquiler-desde")) $("#filter-alquiler-desde").value = "";
    if ($("#filter-alquiler-hasta")) $("#filter-alquiler-hasta").value = "";
    if ($("#filter-alquiler-pago")) $("#filter-alquiler-pago").value = "";
    loadAlquileres();
}

async function loadAlquileres(page = 1) {
    if (!$("#alquileres-tbody")) return;

    const params = new URLSearchParams({ page, per_page: 15 });
    if (state.alquilerFilters.q) params.set("search", state.alquilerFilters.q);
    if (state.alquilerFilters.pago) params.set("estado_pago", state.alquilerFilters.pago);
    if (state.alquilerFilters.fecha_desde) params.set("fecha_desde", state.alquilerFilters.fecha_desde);
    if (state.alquilerFilters.fecha_hasta) params.set("fecha_hasta", state.alquilerFilters.fecha_hasta);

    try {
        const response = await api.get(`/alquileres?${params.toString()}`);
        state.alquileres = response.data;
        renderAlquileresTable();
        if (response.meta) {
            renderAuditPagination("alquileres-pagination", response.meta, (newPage) => loadAlquileres(newPage));
        }
    } catch (error) {
        showToast(error.message, "error");
    }
}

function renderAlquileresTable() {
    const tbody = $("#alquileres-tbody");
    if (!tbody) return;

    if (!state.alquileres.length) {
        tbody.innerHTML = `<tr><td class="empty-state" colspan="7">No hay registros de alquileres.</td></tr>`;
        return;
    }

    tbody.innerHTML = state.alquileres.map((a) => {
        // Mapeo exacto a las columnas de tu Base de Datos
        const estado = a.cuenta || "-"; 
        const badgeClass = estado === "Cobrado" ? "pagado" : "contra";
        const fecha = a.dia || a.fecha;
        const total = a.importe_proyecto || 0;

        return `
            <tr>
                <td class="cell-code">#${a.id}</td>
                <td class="cell-muted">${formatDate(fecha)}</td>
                <td><strong>${escapeHtml(a.cliente?.nombre || a.cliente_nombre || "-")}</strong></td>
                <td><span class="cell-code">${escapeHtml(a.maquinaria?.placa || a.maquinaria_placa || "-")}</span></td>
                <td class="col-hide-mob"><span class="badge ${badgeClass}">${escapeHtml(estado)}</span></td>
                <td class="col-hide-mob cell-code"><strong>${money(total)}</strong></td>
                <td>
                    <div class="row-actions">
                        <span class="material-icon" data-alquiler-details="${a.id}" title="Ver detalle" role="button" tabindex="0" aria-label="Ver detalle de alquiler">visibility</span>
                    </div>
                </td>
            </tr>
        `;
    }).join("");

    tbody.querySelectorAll("[data-alquiler-details]").forEach((button) => {
        button.addEventListener("click", () => showAlquilerDetails(Number(button.dataset.alquilerDetails)));
    });
}

function openAlquilerModal() {
    $("#alquiler-form").reset();
    $("#alquiler-db-id").value = "";
    $("#alquiler-cliente-id").value = "";
    $("#alquiler-maquinaria-id").value = "";
    
    // Captura fecha de hoy automática
    const inputAlquilerFecha = $("#alquiler-fecha");
    if (inputAlquilerFecha && !inputAlquilerFecha.value) {
        inputAlquilerFecha.value = new Date().toISOString().slice(0, 10);
    }
    $("#alquiler-preview-importe").value = "0.00";
    $("#alquiler-cliente-status").textContent = "";
    $("#alquiler-maquinaria-status").textContent = "";

    toggleMetodoPagoAlquiler();

    $("#alquiler-modal").classList.add("open");
    $("#alquiler-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeAlquilerModal() {
    $("#alquiler-modal").classList.remove("open");
    $("#alquiler-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    closeDropdowns();
}

function calcularPreviewAlquiler() {
    const tiempo = parseFloat($("#alquiler-tiempo")?.value) || 0;
    const precio = parseFloat($("#alquiler-precio")?.value) || 0;
    const total = tiempo * precio;
    const preview = $("#alquiler-preview-importe");
    if (preview) {
        preview.value = total.toLocaleString("es-PE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function toggleMetodoPagoAlquiler() {
    const estado = $("#alquiler-estado-pago")?.value;
    const metodoSelect = $("#alquiler-metodo-pago");
    if (!metodoSelect) return;

    if (estado === "Pendiente") {
        metodoSelect.disabled = true;
        metodoSelect.value = "";
    } else {
        metodoSelect.disabled = false;
        if (!metodoSelect.value) metodoSelect.value = "Efectivo";
    }
}

function initAlquilerAutocompleteMaquinaria() {
    const input = $("#alquiler-maquinaria-search");
    const dropdown = $("#alquiler-maquinaria-dropdown");
    const hiddenId = $("#alquiler-maquinaria-id");
    const status = $("#alquiler-maquinaria-status");
    if (!input || !dropdown) return;

    input.addEventListener("input", async (e) => {
        const query = e.target.value.trim().toUpperCase();
        hiddenId.value = "";
        status.textContent = "";

        if (query.length < 2) {
            dropdown.innerHTML = "";
            dropdown.classList.remove("open");
            return;
        }

        try {
            const res = await api.get(`/maquinarias?q=${encodeURIComponent(query)}&activo=1`);
            const items = res.data || [];

            if (!items.length) {
                dropdown.innerHTML = '<div style="padding: 10px; color: var(--muted); font-size: 0.85rem;">No hay maquinarias activas encontradas</div>';
                dropdown.classList.add("open");
                return;
            }

            dropdown.innerHTML = items.map(m => `
                <button type="button" data-id="${m.id}" data-placa="${escapeHtml(m.placa)}" data-desc="${escapeHtml(m.descripcion)}" style="display: flex; justify-content: space-between; align-items: center; width: 100%; text-align: left;">
                    <strong style="color: var(--accent);">${escapeHtml(m.placa)}</strong>
                    <small style="color: var(--muted);">${escapeHtml(m.descripcion)}</small>
                </button>
            `).join("");
            dropdown.classList.add("open");

            dropdown.querySelectorAll("button").forEach(btn => {
                btn.addEventListener("click", () => {
                    hiddenId.value = btn.dataset.id;
                    input.value = btn.dataset.placa;
                    status.textContent = `Seleccionada: ${btn.dataset.placa} (${btn.dataset.desc})`;
                    dropdown.classList.remove("open");
                });
            });
        } catch (err) {
            dropdown.classList.remove("open");
        }
    });
}

function initAlquilerAutocompleteCliente() {
    const input = $("#alquiler-cliente-search");
    const dropdown = $("#alquiler-cliente-dropdown");
    const hiddenId = $("#alquiler-cliente-id");
    const status = $("#alquiler-cliente-status");
    if (!input || !dropdown) return;

    input.addEventListener("input", async (e) => {
        const query = e.target.value.trim();
        hiddenId.value = "";
        status.textContent = "";

        if (query.length < 2) {
            dropdown.innerHTML = "";
            dropdown.classList.remove("open");
            return;
        }

        try {
            const res = await api.get(`/clientes?search=${encodeURIComponent(query)}`);
            const clientes = (res.data || []).slice(0, 5);

            if (!clientes.length) {
                dropdown.innerHTML = '<div style="padding: 10px; color: var(--muted); font-size: 0.85rem;">Cliente no encontrado</div>';
                dropdown.classList.add("open");
                return;
            }

            dropdown.innerHTML = clientes.map(c => `
                <button type="button" data-id="${c.id}" data-nombre="${escapeHtml(c.nombre)}" data-doc="${escapeHtml(c.dni || '')}" style="display: flex; justify-content: space-between; align-items: center; width: 100%; text-align: left;">
                    <span>${escapeHtml(c.nombre)}</span>
                    <small style="color: var(--muted);">${c.dni ? (c.tipo_documento ? c.tipo_documento.toUpperCase() + ': ' : '') + escapeHtml(c.dni) : 'Sin documento'}</small>
                </button>
            `).join("");
            dropdown.classList.add("open");

            dropdown.querySelectorAll("button").forEach(btn => {
                btn.addEventListener("click", () => {
                    hiddenId.value = btn.dataset.id;
                    input.value = btn.dataset.nombre;
                    status.textContent = `Cliente: ${btn.dataset.nombre} ${btn.dataset.doc ? `(${btn.dataset.doc})` : ''}`;
                    dropdown.classList.remove("open");
                });
            });
        } catch (err) {
            dropdown.classList.remove("open");
        }
    });
}

async function saveAlquiler(event) {
    event.preventDefault();

    const clienteId = $("#alquiler-cliente-id").value;
    const maquinariaId = $("#alquiler-maquinaria-id").value;

    if (!clienteId) {
        showToast("Selecciona un cliente válido de la lista.", "error");
        return;
    }
    if (!maquinariaId) {
        showToast("Selecciona una maquinaria válida de la lista.", "error");
        return;
    }

    // Calcular el importe antes de enviar al backend
    const tiempo = parseFloat($("#alquiler-tiempo").value) || 0;
    const precio = parseFloat($("#alquiler-precio").value) || 0;
    const importeCalculado = tiempo * precio;

    // Payload con los nombres EXACTOS de la base de datos

    const cuentaEstado = $("#alquiler-estado-pago").value;
    const destinoPago = cuentaEstado === "Pendiente" 
        ? "Por definir" 
        : ($("#alquiler-metodo-pago")?.value || "Por definir");

    const payload = {
        dia: $("#alquiler-fecha").value,
        cliente_id: parseInt(clienteId, 10),
        proyecto: $("#alquiler-proyecto-search")?.value.trim() || "",
        maquinaria_id: parseInt(maquinariaId, 10),
        horas: tiempo,
        precio: precio,
        importe_proyecto: importeCalculado,
        cuenta: cuentaEstado,
        destino_pago: destinoPago,
        comprobante: $("#alquiler-comprobante")?.value || "S. CP",
        observacion: $("#alquiler-obs")?.value.trim() || null,
    };

    try {
        await api.post("/alquileres", payload);
        showToast("Alquiler registrado correctamente.");
        closeAlquilerModal();
        loadAlquileres();
    } catch (error) {
        showToast(error.message, "error");
    }
}

function showAlquilerDetails(id) {
    const a = state.alquileres.find((item) => item.id === id);
    if (!a) return;

    $("#alquiler-details-title").textContent = `Detalle de Alquiler #${a.id}`;

    // Mapeo exacto a las columnas de tu Base de Datos
    const estado = a.cuenta || "-";
    const badgeClass = estado === "Cobrado" ? "pagado" : "contra";
    const fecha = a.dia || a.fecha;
    const total = a.importe_proyecto || 0;
    const metodoPago = a.destino_pago || 'No especificado / Pendiente';
    const horas = a.horas || 0;
    const tarifa = a.precio || 0;
    const proyecto = a.proyecto || 'No asignado';
    const notas = a.observacion || 'Sin notas registradas.';

    $("#alquiler-details-body").innerHTML = `
        <div style="background: var(--panel-soft); border-radius: 8px; padding: 14px; margin-bottom: 1.25rem; border: 1px solid var(--line);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <span style="color: var(--muted); font-size: 0.85rem;">Comprobante: <strong>${escapeHtml(a.comprobante || 'Sin comprobante')}</strong></span>
                <span class="badge ${badgeClass}">${escapeHtml(estado)}</span>
            </div>
            <div style="font-size: 1.6rem; font-weight: 800; color: var(--accent); font-family: 'Outfit', sans-serif;">
                ${money(total)}
            </div>
            <small style="color: var(--muted);">Método de pago: ${escapeHtml(metodoPago)}</small>
        </div>

        <div class="detail-grid">
            <div><span>Fecha de inicio</span><strong>${formatDate(fecha)}</strong></div>
            <div><span>Cliente</span><strong>${escapeHtml(a.cliente?.nombre || a.cliente_nombre || '-')}</strong></div>
            <div><span>Maquinaria</span><strong>${escapeHtml(a.maquinaria?.placa || a.maquinaria_placa || '-')}</strong></div>
            <div><span>Proyecto / Obra</span><strong>${escapeHtml(proyecto)}</strong></div>
            <div><span>Tiempo trabajado</span><strong>${horas} (hrs / días)</strong></div>
            <div><span>Tarifa unitaria</span><strong>${money(tarifa)}</strong></div>
            <div class="span-all">
                <span>Observaciones</span>
                <p>${escapeHtml(notas)}</p>
            </div>
        </div>
    `;

    $("#alquiler-details-modal").classList.add("open");
    $("#alquiler-details-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeAlquilerDetailsModal() {
    $("#alquiler-details-modal").classList.remove("open");
    $("#alquiler-details-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
}

function filterParams() {
    const params = new URLSearchParams();
    Object.entries(state.filters).forEach(([key, value]) => {
        if (value) params.set(key, value);
    });
    return params;
}

function closeDropdowns() {
    document.querySelectorAll(".dropdown").forEach((dropdown) => dropdown.classList.remove("open"));
}

function showToast(message, type = "success") {
    const toast = $("#toast");
    toast.textContent = message;
    toast.className = `toast show ${type === "error" ? "error" : ""}`;
    setTimeout(() => toast.classList.remove("show"), 3200);
}

function debounce(callback, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => callback(...args), delay);
    };
}

function formatDate(value) {
    if (!value) return "-";
    return new Date(`${value}T12:00:00`).toLocaleDateString("es-PE", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}

function money(value) {
    if (value === null || value === undefined || value === "") return "-";
    return `S/ ${Number(value).toFixed(2)}`;
}

function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
    }[char]));
}

function errorMessage(payload) {
    if (payload.message) return payload.message;
    if (payload.errors) {
        const first = Object.values(payload.errors).flat()[0];
        if (first) return first;
    }
    return "No se pudo completar la solicitud.";
}

let dashboardChartInstance = null;

async function loadDashboardGanancias() {
    const presetSelect = document.getElementById("dash-preset");
    const customDates = document.getElementById("dash-custom-dates");
    const fechaDesdeInput = document.getElementById("dash-fecha-desde");
    const fechaHastaInput = document.getElementById("dash-fecha-hasta");
    const btnFiltrar = document.getElementById("dash-btn-filtrar");

    if (!presetSelect) return;

    if (!presetSelect.dataset.listenerAttached) {
        presetSelect.dataset.listenerAttached = "true";
        presetSelect.addEventListener("change", () => {
            const isCustom = presetSelect.value === "personalizado";
            if (customDates) customDates.style.display = isCustom ? "flex" : "none";
            if (!isCustom) {
                loadDashboardGanancias();
            }
        });

        btnFiltrar?.addEventListener("click", () => {
            loadDashboardGanancias();
        });
    }

    const preset = presetSelect.value;
    const params = new URLSearchParams({ preset });

    if (preset === "personalizado") {
        if (fechaDesdeInput?.value) params.append("fecha_desde", fechaDesdeInput.value);
        if (fechaHastaInput?.value) params.append("fecha_hasta", fechaHastaInput.value);
    }

    try {
        const res = await fetch(`/api/dashboard/ganancias?${params.toString()}`, {
            headers: {
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });

        if (!res.ok) throw new Error("Error al obtener indicadores");

        const data = await res.json();
        const kpi = data.kpis;
        const sol = (n) => "S/ " + Number(n).toLocaleString("es-PE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const elIngresos = document.getElementById("kpi-ingresos");
        const elVolumen = document.getElementById("kpi-volumen");
        const elCostos = document.getElementById("kpi-costos");
        const elUtilidad = document.getElementById("kpi-utilidad");
        const elMargen = document.getElementById("kpi-margen");

        if (elIngresos) elIngresos.textContent = sol(kpi.ingresos);
        if (elVolumen) elVolumen.textContent = `${kpi.total_envios} despachos`;
        if (elCostos) elCostos.textContent = sol(kpi.fletes);
        if (elUtilidad) elUtilidad.textContent = sol(kpi.utilidad);
        if (elMargen) elMargen.textContent = `${kpi.margen_pct}%`;

        const topCliBody = document.getElementById("dash-top-clientes-body");
        if (topCliBody) {
            if (!data.top_deudores || data.top_deudores.length === 0) {
                topCliBody.innerHTML = `<div style="text-align:center; padding:18px; color:var(--muted); font-size:0.85rem;">Sin deudas pendientes registradas.</div>`;
            } else {
                topCliBody.innerHTML = data.top_deudores.map((c, index) => `
                    <div style="display: flex; justify-content: space-between; align-items: center; background: var(--panel-soft); border: 1px solid var(--line); padding: 10px 14px; border-radius: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted); background: var(--panel-strong); width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                ${index + 1}
                            </span>
                            <div style="display: flex; flex-direction: column; min-width: 0;">
                                <strong style="color: var(--text); font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    ${c.cliente}
                                </strong>
                                <span style="color: var(--faint); font-size: 0.76rem;">
                                    ${c.envios_pendientes} ${c.envios_pendientes === 1 ? 'envío pendiente' : 'envíos pendientes'}
                                </span>
                            </div>
                        </div>
                        <div style="text-align: right; margin-left: 12px; flex-shrink: 0;">
                            <div style="color: var(--danger); font-weight: 700; font-size: 0.95rem; font-family: 'Outfit', sans-serif;">
                                ${sol(c.deuda_total)}
                            </div>
                        </div>
                    </div>
                `).join("");
            }
        }

        const topTransBody = document.getElementById("dash-top-transportistas-body");
        if (topTransBody) {
            if (!data.top_transportistas || data.top_transportistas.length === 0) {
                topTransBody.innerHTML = `<div style="text-align:center; padding:18px; color:var(--muted); font-size:0.85rem;">Sin saldos pendientes de flete.</div>`;
            } else {
                topTransBody.innerHTML = data.top_transportistas.map((t, index) => `
                    <div style="display: flex; justify-content: space-between; align-items: center; background: var(--panel-soft); border: 1px solid var(--line); padding: 10px 14px; border-radius: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted); background: var(--panel-strong); width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                ${index + 1}
                            </span>
                            <div style="display: flex; flex-direction: column; min-width: 0;">
                                <strong style="color: var(--text); font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    ${t.transportista}
                                </strong>
                                <span style="color: var(--faint); font-size: 0.76rem;">
                                    ${t.viajes_pendientes} ${t.viajes_pendientes === 1 ? 'viaje por liquidar' : 'viajes por liquidar'}
                                </span>
                            </div>
                        </div>
                        <div style="text-align: right; margin-left: 12px; flex-shrink: 0;">
                            <div style="color: var(--accent); font-weight: 700; font-size: 0.95rem; font-family: 'Outfit', sans-serif;">
                                ${sol(t.total_pendiente)}
                            </div>
                        </div>
                    </div>
                `).join("");
            }
        }

        const ctx = document.getElementById("dash-chart-canvas");
        if (ctx && data.chart) {
            if (dashboardChartInstance) {
                dashboardChartInstance.destroy();
            }

            dashboardChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.chart.labels,
                    datasets: [
                        {
                            label: 'Ingresos (S/)',
                            data: data.chart.ingresos,
                            backgroundColor: 'rgba(96, 165, 250, 0.75)',
                            borderColor: '#60a5fa',
                            borderWidth: 1,
                            borderRadius: 4,
                            order: 2,
                        },
                        {
                            label: 'Costo Fletes (S/)',
                            data: data.chart.fletes,
                            backgroundColor: 'rgba(239, 100, 100, 0.75)',
                            borderColor: '#ef6464',
                            borderWidth: 1,
                            borderRadius: 4,
                            order: 2,
                        },
                        {
                            label: 'Margen Neto (S/)',
                            data: data.chart.ganancia,
                            type: 'line',
                            borderColor: '#c5a043',
                            backgroundColor: '#c5a043',
                            borderWidth: 2,
                            pointRadius: 4,
                            pointBackgroundColor: '#c5a043',
                            fill: false,
                            tension: 0.3,
                            order: 1,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#94a3b8',
                                font: { family: 'Inter', size: 12 }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#101827',
                            titleColor: '#eef2f7',
                            bodyColor: '#eef2f7',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            padding: 10,
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 } }
                        },
                        y: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: {
                                color: '#94a3b8',
                                font: { family: 'Inter', size: 11 },
                                callback: (val) => 'S/ ' + val
                            }
                        }
                    }
                }
            });
        }

    } catch (err) {
        console.error("Error al cargar dashboard ejecutivo:", err);
    }
}

let auditCurrentTab = 'transportistas';
let auditTransPage = 1;
let auditCobroPage = 1;
let auditTransChartInstance = null;
let auditCobroChartInstance = null;

function setupAuditTabs() {
    const btnTrans = document.getElementById('tab-btn-audit-transportistas');
    const btnCobros = document.getElementById('tab-btn-audit-cobros');
    const contentTrans = document.getElementById('audit-tab-transportistas-content');
    const contentCobros = document.getElementById('audit-tab-cobros-content');

    if (!btnTrans || !btnCobros) return;

    btnTrans.onclick = () => {
        auditCurrentTab = 'transportistas';
        btnTrans.style.background = 'var(--accent)';
        btnTrans.style.color = '#000';
        btnCobros.style.background = 'var(--panel-soft)';
        btnCobros.style.color = 'var(--muted)';

        contentTrans.classList.remove('hidden');
        contentCobros.classList.add('hidden');
        cargarAuditoriaTransportistas(auditTransPage);
    };

    btnCobros.onclick = () => {
        auditCurrentTab = 'cobros';
        btnCobros.style.background = 'var(--accent)';
        btnCobros.style.color = '#000';
        btnTrans.style.background = 'var(--panel-soft)';
        btnTrans.style.color = 'var(--muted)';

        contentCobros.classList.remove('hidden');
        contentTrans.classList.add('hidden');
        cargarAuditoriaCobros(auditCobroPage);
    };

    const btnFiltroTrans = document.getElementById('audit-trans-btn-filtrar');
    if (btnFiltroTrans) btnFiltroTrans.onclick = () => cargarAuditoriaTransportistas(1);

    const btnExportTrans = document.getElementById('audit-trans-btn-export');
    if (btnExportTrans) btnExportTrans.onclick = () => exportarAuditoriaTransportistas();

    const btnFiltroCobros = document.getElementById('audit-cobro-btn-filtrar');
    if (btnFiltroCobros) btnFiltroCobros.onclick = () => cargarAuditoriaCobros(1);

    const btnExportCobros = document.getElementById('audit-cobro-btn-export');
    if (btnExportCobros) btnExportCobros.onclick = () => exportarAuditoriaCobros();

    const inputBuscarTrans = document.getElementById('audit-trans-filter-buscar');
    if (inputBuscarTrans) {
        inputBuscarTrans.onkeyup = (e) => {
            if (e.key === 'Enter') cargarAuditoriaTransportistas(1);
        };
    }

    const inputBuscarCobro = document.getElementById('audit-cobro-filter-buscar');
    if (inputBuscarCobro) {
        inputBuscarCobro.onkeyup = (e) => {
            if (e.key === 'Enter') cargarAuditoriaCobros(1);
        };
    }
}

async function cargarAuditoriaTransportistas(page = 1) {
    auditTransPage = page;
    const inicio = document.getElementById('audit-trans-filter-inicio')?.value || '';
    const fin = document.getElementById('audit-trans-filter-fin')?.value || '';
    const buscar = document.getElementById('audit-trans-filter-buscar')?.value || '';

    const params = new URLSearchParams({ page });
    if (inicio) params.append('fecha_inicio', inicio);
    if (fin) params.append('fecha_fin', fin);
    if (buscar) params.append('buscar', buscar);

    const tbody = document.getElementById('audit-trans-table-body');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 2rem; color: var(--muted);">Cargando registros...</td></tr>';

    try {
        const res = await fetch(`/api/auditoria/transportistas?${params.toString()}`);
        const resData = await res.json();
        if (!resData.ok) throw new Error(resData.message || 'Error en respuesta');

        document.getElementById('audit-trans-kpi-total').textContent = `S/ ${(resData.kpis?.total_desembolsado || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        document.getElementById('audit-trans-kpi-abonos').textContent = resData.kpis?.abonos_efectuados || 0;
        document.getElementById('audit-trans-kpi-choferes').textContent = resData.kpis?.choferes_saldados || 0;

        const pagos = resData.data?.data || [];
        renderMixedTimelineChart('audit-trans-chart', pagos, 'fecha_pago', 'monto_total', '#ef6464', 'Desembolsos', auditTransChartInstance, (inst) => auditTransChartInstance = inst);

        if (pagos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 2rem; color: var(--muted);">No se encontraron desembolsos en este período.</td></tr>';
            document.getElementById('audit-trans-pagination').innerHTML = '';
            return;
        }

        tbody.innerHTML = pagos.map(p => {
            const guiasInfo = p.items && p.items.length 
                ? p.items.map(it => it.envio ? `${it.envio.codigo} (${it.envio.guia || 'Sin guía'})` : '').filter(Boolean).join(', ')
                : (p.observacion || 'Flete liquidado');

            return `
                <tr style="border-bottom: 1px solid var(--line);">
                    <td style="padding: 0.85rem 1rem; font-weight: 700; color: var(--accent);">#${p.numero_recibo || p.id}</td>
                    <td style="padding: 0.85rem 1rem; color: var(--muted);">${new Date(p.fecha_pago).toLocaleDateString('es-PE')} <small>${new Date(p.fecha_pago).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })}</small></td>
                    <td style="padding: 0.85rem 1rem; font-weight: 600; color: var(--text);">${p.transportista?.nombre || 'N/A'}</td>
                    <td style="padding: 0.85rem 1rem; font-weight: 700; color: var(--danger);">- S/ ${parseFloat(p.monto_total || 0).toFixed(2)}</td>
                    <td style="padding: 0.85rem 1rem; color: var(--muted);">${p.user?.name || 'Sistema'}</td>
                    <td style="padding: 0.85rem 1rem; color: var(--muted); font-size: 0.85rem;" title="${guiasInfo}">${guiasInfo.length > 40 ? guiasInfo.substring(0, 40) + '...' : guiasInfo}</td>
                </tr>
            `;
        }).join('');

        renderAuditPagination('audit-trans-pagination', resData.data, (newPage) => cargarAuditoriaTransportistas(newPage));
    } catch (e) {
        console.error('Error cargando auditoría de transportistas:', e);
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color: var(--danger); padding: 2rem;">Error al consultar datos.</td></tr>';
    }
}

function exportarAuditoriaTransportistas() {
    const inicio = document.getElementById('audit-trans-filter-inicio')?.value || '';
    const fin = document.getElementById('audit-trans-filter-fin')?.value || '';
    const buscar = document.getElementById('audit-trans-filter-buscar')?.value || '';

    const params = new URLSearchParams();
    if (inicio) params.append('fecha_inicio', inicio);
    if (fin) params.append('fecha_fin', fin);
    if (buscar) params.append('buscar', buscar);

    window.location.href = `/api/auditoria/transportistas/export?${params.toString()}`;
}

async function cargarAuditoriaCobros(page = 1) {
    auditCobroPage = page;
    const inicio = document.getElementById('audit-cobro-filter-inicio')?.value || '';
    const fin = document.getElementById('audit-cobro-filter-fin')?.value || '';
    const buscar = document.getElementById('audit-cobro-filter-buscar')?.value || '';
    const servicio = document.getElementById('audit-cobro-filter-servicio')?.value || '';

    const params = new URLSearchParams({ page });
    if (inicio) params.append('fecha_inicio', inicio);
    if (fin) params.append('fecha_fin', fin);
    if (buscar) params.append('buscar', buscar);
    if (servicio) params.append('servicio', servicio);

    const tbody = document.getElementById('audit-cobro-table-body');
    if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 2rem; color: var(--muted);">Cargando registros...</td></tr>';

    try {
        const res = await fetch(`/api/auditoria/cobros?${params.toString()}`);
        const resData = await res.json();
        if (!resData.ok) throw new Error(resData.message || 'Error en respuesta');

        document.getElementById('audit-cobro-kpi-total').textContent = `S/ ${(resData.kpis?.total_recaudado || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        document.getElementById('audit-cobro-kpi-cantidad').textContent = resData.kpis?.abonos_realizados || 0;
        document.getElementById('audit-cobro-kpi-clientes').textContent = resData.kpis?.clientes_abonaron || 0;

        const cobros = resData.data?.data || [];
        renderMixedTimelineChart('audit-cobros-chart', cobros, 'fecha', 'monto', '#4ade80', 'Cobranzas', auditCobroChartInstance, (inst) => auditCobroChartInstance = inst);

        if (cobros.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 2rem; color: var(--muted);">No se encontraron cobros en este período.</td></tr>';
            document.getElementById('audit-cobro-pagination').innerHTML = '';
            return;
        }

        tbody.innerHTML = cobros.map(c => {
            const saldo = parseFloat(c.saldo_acumulado || 0);
            const textoSaldo = saldo < 0 
                ? `<span style="color: var(--success); font-weight: 600;">S/ ${Math.abs(saldo).toFixed(2)} (A favor)</span>` 
                : (saldo === 0 ? `<span style="color: var(--muted);">S/ 0.00 (Saldado)</span>` : `<span style="color: var(--blue); font-weight: 600;">S/ ${saldo.toFixed(2)}</span>`);

            return `
                <tr style="border-bottom: 1px solid var(--line);">
                    <td style="padding: 0.85rem 1rem; color: var(--muted);">${c.fecha}</td>
                    <td style="padding: 0.85rem 1rem; font-weight: 600; color: var(--text);">${c.cliente?.nombre || 'N/A'}</td>
                    <td style="padding: 0.85rem 1rem; font-weight: 700; color: var(--success);">+ S/ ${parseFloat(c.monto || 0).toFixed(2)}</td>
                    <td style="padding: 0.85rem 1rem;">${textoSaldo}</td>
                    <td style="padding: 0.85rem 1rem; color: var(--muted);"><small>${c.observacion || 'Abono a línea de crédito'}</small></td>
                </tr>
            `;
        }).join('');

        renderAuditPagination('audit-cobro-pagination', resData.data, (newPage) => cargarAuditoriaCobros(newPage));
    } catch (e) {
        console.error('Error cargando auditoría de cobros:', e);
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: var(--danger); padding: 2rem;">Error al consultar datos.</td></tr>';
    }
}

function exportarAuditoriaCobros() {
    const inicio = document.getElementById('audit-cobro-filter-inicio')?.value || '';
    const fin = document.getElementById('audit-cobro-filter-fin')?.value || '';
    const buscar = document.getElementById('audit-cobro-filter-buscar')?.value || '';
    const servicio = document.getElementById('audit-cobro-filter-servicio')?.value || ''; // <-- Capturar aquí también

    const params = new URLSearchParams();
    if (inicio) params.append('fecha_inicio', inicio);
    if (fin) params.append('fecha_fin', fin);
    if (buscar) params.append('buscar', buscar);
    if (servicio) params.append('servicio', servicio); // <-- Adjuntar aquí también

    window.location.href = `/api/auditoria/cobros/export?${params.toString()}`;
}

function renderMixedTimelineChart(canvasId, records, dateField, amountField, barColor, labelName, currentChartInstance, setChartInstance) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;

    if (currentChartInstance) {
        currentChartInstance.destroy();
    }

    const agrupado = {};
    records.forEach(r => {
        const rawDate = r[dateField];
        const dateKey = rawDate ? rawDate.substring(0, 10) : 'Sin fecha';
        agrupado[dateKey] = (agrupado[dateKey] || 0) + parseFloat(r[amountField] || 0);
    });

    const labels = Object.keys(agrupado).sort();
    const dataDiaria = labels.map(k => agrupado[k]);

    let runningTotal = 0;
    const dataAcumulada = dataDiaria.map(val => {
        runningTotal += val;
        return runningTotal;
    });

    const newChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['Sin operaciones'],
            datasets: [
                {
                    type: 'line',
                    label: `Acumulado ${labelName} (S/)`,
                    data: dataAcumulada.length ? dataAcumulada : [0],
                    borderColor: '#c5a043',
                    backgroundColor: '#c5a043',
                    borderWidth: 2.5,
                    pointRadius: 5,
                    pointHoverRadius: 7.5,
                    pointBackgroundColor: '#c5a043',
                    pointBorderColor: '#0b1120',
                    pointBorderWidth: 2,
                    fill: false,
                    tension: 0.25
                },
                {
                    type: 'bar',
                    label: `Movimiento Diario (S/)`,
                    data: dataDiaria.length ? dataDiaria : [0],
                    backgroundColor: barColor,
                    borderRadius: 5,
                    maxBarThickness: 36
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: { top: 12, bottom: 6, left: 6, right: 12 }
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        color: '#e2e8f0',
                        font: { family: 'Inter', size: 12, weight: '600' },
                        boxWidth: 14,
                        padding: 15
                    }
                },
                tooltip: {
                    backgroundColor: '#0b1120',
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    borderColor: 'rgba(197, 160, 67, 0.35)',
                    borderWidth: 1,
                    padding: 10,
                    titleFont: { size: 12, weight: '700' },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: (ctx) => ` ${ctx.dataset.label}: S/ ${Number(ctx.parsed.y).toLocaleString('es-PE', { minimumFractionDigits: 2 })}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 11, weight: '500' },
                        padding: 6
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.06)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#cbd5e1',
                        font: { size: 12, weight: '600' },
                        padding: 8,
                        callback: (v) => 'S/ ' + Number(v).toLocaleString('es-PE')
                    }
                }
            }
        }
    });

    setChartInstance(newChart);
}

function renderAuditPagination(containerId, meta, onPageChange) {
    const container = document.getElementById(containerId);
    if (!container || !meta) return;

    const page = meta.current_page || 1;
    const lastPage = meta.last_page || 1;

    if (lastPage <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';
    html += `<button class="pagination-btn" style="background: var(--panel); border: 1px solid var(--line); color: var(--text); padding: 0.35rem 0.65rem; border-radius: 4px; cursor: pointer;" ${page <= 1 ? "disabled" : ""} data-p="1">&laquo;</button>`;
    html += `<button class="pagination-btn" style="background: var(--panel); border: 1px solid var(--line); color: var(--text); padding: 0.35rem 0.65rem; border-radius: 4px; cursor: pointer;" ${page <= 1 ? "disabled" : ""} data-p="${page - 1}">&lsaquo;</button>`;

    for (let i = Math.max(1, page - 2); i <= Math.min(lastPage, page + 2); i++) {
        const isActive = i === page;
        html += `<button class="pagination-btn" style="background: ${isActive ? 'var(--accent)' : 'var(--panel)'}; color: ${isActive ? '#000' : 'var(--text)'}; font-weight: ${isActive ? '700' : '400'}; border: 1px solid var(--line); padding: 0.35rem 0.65rem; border-radius: 4px; cursor: pointer;" data-p="${i}">${i}</button>`;
    }

    html += `<button class="pagination-btn" style="background: var(--panel); border: 1px solid var(--line); color: var(--text); padding: 0.35rem 0.65rem; border-radius: 4px; cursor: pointer;" ${page >= lastPage ? "disabled" : ""} data-p="${page + 1}">&rsaquo;</button>`;
    html += `<button class="pagination-btn" style="background: var(--panel); border: 1px solid var(--line); color: var(--text); padding: 0.35rem 0.65rem; border-radius: 4px; cursor: pointer;" ${page >= lastPage ? "disabled" : ""} data-p="${lastPage}">&raquo;</button>`;

    container.innerHTML = html;
    container.querySelectorAll('.pagination-btn[data-p]').forEach(btn => {
        btn.onclick = () => {
            const p = parseInt(btn.getAttribute('data-p'));
            if (!isNaN(p) && p !== page && p >= 1 && p <= lastPage) {
                onPageChange(p);
            }
        };
    });
}

function setupLiveAutocomplete(inputId, dropdownId, urlEndpoint, docField, onSelectCallback) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    if (!input || !dropdown) return;

    let timer = null;

    input.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        clearTimeout(timer);

        if (query.length < 2) {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            return;
        }

        timer = setTimeout(async () => {
            try {
                const res = await fetch(`${urlEndpoint}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();

                if (!json.ok || !json.data || json.data.length === 0) {
                    dropdown.style.display = 'none';
                    dropdown.innerHTML = '';
                    return;
                }

                dropdown.innerHTML = json.data.map(item => `
                    <div class="ac-item" data-name="${item.nombre}" style="padding: 0.7rem 0.9rem; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 600; color: #f8fafc; font-size: 0.88rem;">${item.nombre}</span>
                        <span style="color: #c5a043; font-family: monospace; font-size: 0.78rem; background: rgba(197, 160, 67, 0.12); padding: 2px 6px; border-radius: 4px;">Doc: ${item[docField] || 'S/D'}</span>
                    </div>
                `).join('');

                dropdown.style.display = 'block';

                dropdown.querySelectorAll('.ac-item').forEach(el => {
                    el.onmouseenter = () => el.style.backgroundColor = 'rgba(197, 160, 67, 0.18)';
                    el.onmouseleave = () => el.style.backgroundColor = 'transparent';
                    el.onclick = () => {
                        input.value = el.getAttribute('data-name');
                        dropdown.style.display = 'none';
                        onSelectCallback();
                    };
                });
            } catch (err) {
                dropdown.style.display = 'none';
            }
        }, 200);
    });

    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

function initAuditoriaFinanciera() {
    setupAuditTabs();
    setupLiveAutocomplete(
        'audit-trans-filter-buscar', 
        'audit-trans-autocomplete-list', 
        '/api/auditoria/transportistas/buscar-rapido', 
        'documento', 
        () => cargarAuditoriaTransportistas(1)
    );
    setupLiveAutocomplete(
        'audit-cobro-filter-buscar', 
        'audit-cobro-autocomplete-list', 
        '/api/auditoria/clientes/buscar-rapido', 
        'dni', 
        () => cargarAuditoriaCobros(1)
    );
    cargarAuditoriaTransportistas(1);
}

function actualizarCardSaldosAbono() {
    const selectOrigen = $("#abono-origen");
    if (!selectOrigen) return;
    
    const origen = selectOrigen.value;
    const isEnvios = origen === 'envios';
    const saldoActivo = isEnvios ? currentAbonoClientData.deudaEnvios : currentAbonoClientData.deudaAlquileres;
    
    const elSaldoActivo = $("#abono-saldo-activo");
    const elBadge = $("#abono-badge-cuenta");
    if (elSaldoActivo) elSaldoActivo.textContent = money(saldoActivo);
    
    if (elBadge) {
        elBadge.textContent = isEnvios ? 'Línea Envíos' : 'Línea Alquileres';
        elBadge.className = `badge ${isEnvios ? 'badge-primary' : 'badge-warning'}`;
        elBadge.style.background = isEnvios ? 'rgba(56, 189, 248, 0.15)' : 'rgba(245, 158, 11, 0.15)';
        elBadge.style.color = isEnvios ? '#38bdf8' : '#f59e0b';
    }

    const boxEnvios = $("#box-sub-envios");
    const boxAlquileres = $("#box-sub-alquileres");
    if (boxEnvios && boxAlquileres) {
        if (isEnvios) {
            boxEnvios.style.background = 'rgba(56, 189, 248, 0.12)';
            boxEnvios.style.border = '1px solid #38bdf8';
            boxAlquileres.style.background = 'var(--panel)';
            boxAlquileres.style.border = '1px solid var(--line)';
        } else {
            boxAlquileres.style.background = 'rgba(245, 158, 11, 0.12)';
            boxAlquileres.style.border = '1px solid #f59e0b';
            boxEnvios.style.background = 'var(--panel)';
            boxEnvios.style.border = '1px solid var(--line)';
        }
    }
}

function setupAbonoModalEvents() {
    $("#abono-origen")?.addEventListener("change", () => {
        actualizarCardSaldosAbono();
        $("#abono-monto").value = ''; 
    });

    $("#box-sub-envios")?.addEventListener("click", () => {
        if ($("#abono-origen")) { $("#abono-origen").value = 'envios'; actualizarCardSaldosAbono(); $("#abono-monto").value = ''; }
    });

    $("#box-sub-alquileres")?.addEventListener("click", () => {
        if ($("#abono-origen")) { $("#abono-origen").value = 'alquileres'; actualizarCardSaldosAbono(); $("#abono-monto").value = ''; }
    });

    $("#btn-abono-saldar-todo")?.addEventListener("click", () => {
        const origen = $("#abono-origen")?.value || 'envios';
        const saldoActivo = origen === 'envios' ? currentAbonoClientData.deudaEnvios : currentAbonoClientData.deudaAlquileres;
        if (saldoActivo > 0) {
            $("#abono-monto").value = parseFloat(saldoActivo).toFixed(2);
        }
    });
}

function setupMovementsTabs() {
    const btnTodos = $("#tab-mov-todos");
    const btnEnvios = $("#tab-mov-envios");
    const btnAlquileres = $("#tab-mov-alquileres");

    if (!btnTodos || !btnEnvios || !btnAlquileres) return;

    const setTabStyle = (activeBtn) => {
        [btnTodos, btnEnvios, btnAlquileres].forEach(btn => {
            const isActive = btn === activeBtn;
            btn.style.background = isActive ? 'var(--accent)' : 'var(--panel-soft)';
            btn.style.color = isActive ? '#000' : 'var(--text)';
            btn.style.fontWeight = isActive ? '700' : '400';
        });
    };

    btnTodos.onclick = () => { currentMovementTab = 'todos'; setTabStyle(btnTodos); renderDebtMovementsInModal(); };
    btnEnvios.onclick = () => { currentMovementTab = 'envios'; setTabStyle(btnEnvios); renderDebtMovementsInModal(); };
    btnAlquileres.onclick = () => { currentMovementTab = 'alquileres'; setTabStyle(btnAlquileres); renderDebtMovementsInModal(); };
}