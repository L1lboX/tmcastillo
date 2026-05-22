const state = {
    envios: [],
    users: [],
    roles: [],
    userRoles: [],
    permissions: [],
    packageTypes: [],
    pendingAmounts: [],
    nextCode: "ENV-0001",
    editingId: null,
    editingUserId: null,
    editingRoleId: null,
    editingPackageTypeId: null,
    liquidatingEnvioId: null,
    filters: {
        q: "",
        pago: "",
        fecha_desde: "",
        fecha_hasta: "",
        cliente: "",
        transportista: "",
        tipo: "",
    },
    spec: { tamano: "", peso: "" },
};

const $ = (selector) => document.querySelector(selector);
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

    delete(path) {
        return this.request(path, { method: "DELETE" });
    },
};

document.addEventListener("DOMContentLoaded", () => {
    initLoginCanvas();

    if (!$("#envio-form")) {
        return;
    }

    bindEvents();
    setToday();
    loadEnvios();
    if (can("clientes.manage") || can("transportistas.manage") || can("envios.create")) {
        loadManagementLists();
    }
    loadPackageTypes(true);
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

    const debouncedClientSearch = debounce(searchClient, 220);
    const debouncedTransportistaSearch = debounce(searchTransportista, 220);

    document.addEventListener("input", (event) => {
        if (event.target?.id === "f-cliente-search") {
            debouncedClientSearch();
        }

        if (event.target?.id === "f-transportista") {
            debouncedTransportistaSearch();
        }

        if (event.target?.id === "f-cantidad") {
            event.target.value = event.target.value.replace(/[^0-9]/g, "");
            updateDetalle();
        }

        if (event.target?.id === "f-tipo") {
            renderTipoDropdown(event.target.value);
            updateDetalle();
        }
    });

    document.addEventListener("focusin", (event) => {
        if (event.target?.id === "f-cliente-search") {
            searchClient();
        }

        if (event.target?.id === "f-transportista") {
            searchTransportista();
        }

        if (event.target?.id === "f-tipo") {
            renderTipoDropdown(event.target.value);
        }
    });

    document.addEventListener("change", (event) => {
        if (event.target?.id === "f-tamano") {
            state.spec.tamano = event.target.value;
            updateDetalle();
        }

        if (event.target?.id === "f-peso") {
            state.spec.peso = event.target.value;
            updateDetalle();
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
    on("#btn-open-package-type-modal", "click", openPackageTypeModal);
    on("#btn-close-package-type-modal", "click", closePackageTypeModal);
    on("#btn-cancel-package-type-modal", "click", closePackageTypeModal);
    on("#btn-close-amount-modal", "click", closeAmountModal);
    on("#btn-cancel-amount-modal", "click", closeAmountModal);
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
    $("#package-type-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "package-type-modal") closePackageTypeModal();
    });
    $("#amount-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "amount-modal") closeAmountModal();
    });
    $("#details-modal")?.addEventListener("click", (event) => {
        if (event.target.id === "details-modal") closeDetailsModal();
    });

    $("#search-input").addEventListener("input", debounce((event) => setFilter("q", event.target.value.trim()), 250));
    $("#filter-pago").addEventListener("change", (event) => setFilter("pago", event.target.value));
    $("#filter-fecha-desde").addEventListener("change", (event) => setFilter("fecha_desde", event.target.value));
    $("#filter-fecha-hasta").addEventListener("change", (event) => setFilter("fecha_hasta", event.target.value));
    $("#filter-cliente").addEventListener("input", debounce((event) => setFilter("cliente", event.target.value.trim()), 250));
    $("#filter-transportista").addEventListener("input", debounce((event) => setFilter("transportista", event.target.value.trim()), 250));
    $("#filter-tipo").addEventListener("input", debounce((event) => setFilter("tipo", event.target.value.trim()), 250));

    $("#envio-form").addEventListener("submit", saveEnvio);
    on("#btn-save-client", "click", saveClient);
    on("#btn-show-client-form", "click", showClientForm);
    on("#client-admin-form", "submit", saveAdminClient);
    on("#carrier-admin-form", "submit", saveAdminCarrier);
    on("#user-form", "submit", saveUser);
    on("#role-form", "submit", saveRole);
    on("#package-type-form", "submit", savePackageType);
    on("#amount-form", "submit", saveAmount);
    on("#role-search-input", "input", debounce((event) => loadRoles(event.target.value.trim()), 250));
    on("#f-guia", "input", (event) => {
        event.target.value = event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
    });

    document.addEventListener("click", (event) => {
        if (!event.target.closest(".field-relative")) {
            closeDropdowns();
        }
    });
}

function showSection(section) {
    const titles = {
        envios: ["Envios", "Registro y control de paquetes"],
        clientes: ["Clientes", "Registro y consulta de clientes"],
        transportistas: ["Transportistas", "Responsables de traslado"],
        dashboard: ["Dashboard", "Indicadores y control del negocio"],
        roles: ["Roles", "Permisos por tipo de usuario"],
        usuarios: ["Usuarios", "Cuentas de acceso al sistema"],
        tipos: ["Tipos de paquete", "Precios por unidad"],
        montos: ["Montos", "Envios pendientes de liquidacion"],
    };

    document.querySelectorAll("[data-page]").forEach((page) => {
        page.classList.toggle("hidden", page.dataset.page !== section);
    });
    document.querySelectorAll("[data-section]").forEach((button) => {
        button.classList.toggle("active", button.dataset.section === section);
    });

    const [title, subtitle] = titles[section] || titles.envios;
    $("#section-title").textContent = title;
    $("#section-subtitle").textContent = subtitle;

    if (section === "clientes" || section === "transportistas") {
        loadManagementLists();
    }
    if (section === "roles") {
        loadRoles();
    }
    if (section === "tipos") {
        loadPackageTypes(false);
    }
    if (section === "montos") {
        loadPendingAmounts();
    }
    if (section === "usuarios") {
        loadUsers();
    }
}

function setFilter(key, value) {
    state.filters[key] = value;
    loadEnvios();
}

async function loadEnvios() {
    const params = filterParams();

    try {
        const response = await api.get(`/envios?${params.toString()}`);
        state.envios = response.data;
        state.nextCode = response.next_code;
        renderTable();
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
        $("#count-display").textContent = `${state.envios.length} registros`;
    } catch {
        $("#count-display").textContent = `${state.envios.length} registros`;
    }
}

function renderTable() {
    const tbody = $("#envios-tbody");

    if (!state.envios.length) {
        tbody.innerHTML = `<tr><td class="empty-state" colspan="${can("envios.amounts") ? 12 : 10}">No hay envios registrados.</td></tr>`;
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
                <td>
                    <strong>${escapeHtml(envio.transportista || "-")}</strong>
                </td>
                <td>${envio.cantidad}</td>
                <td class="cell-muted">${escapeHtml(envio.tipo)}</td>
                <td>${specBadges(envio)}</td>
                <td class="cell-code">${escapeHtml(envio.guia || "-")}</td>
                <td><span class="badge ${badgeClass}">${escapeHtml(envio.pago)}</span></td>
                ${can("envios.amounts") ? `<td class="cell-code">${money(envio.monto)}</td>` : ""}
                ${can("envios.amounts") ? `<td class="cell-code">${money(envio.margen)}</td>` : ""}
                <td>
                    <div class="row-actions">
                        <button class="button icon-only icon-details" type="button" data-details="${envio.id}" title="Ver detalles" aria-label="Ver detalles"></button>
                        ${can("envios.amounts") ? `<button class="button icon-only icon-money" type="button" data-amount="${envio.id}" title="Registrar monto" aria-label="Registrar monto"></button>` : ""}
                        ${can("envios.update") ? `<button class="button icon-only icon-edit" type="button" data-edit="${envio.id}" title="Editar" aria-label="Editar"></button>` : ""}
                        ${can("envios.delete") ? `<button class="button button-danger icon-only icon-delete" type="button" data-delete="${envio.id}" title="Eliminar" aria-label="Eliminar"></button>` : ""}
                    </div>
                </td>
            </tr>
        `;
    }).join("");

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

function openModal() {
    resetForm();
    $("#f-codigo").value = state.nextCode;
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
    $("#client-modal").classList.add("open");
    $("#client-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeClientModal() {
    $("#client-modal").classList.remove("open");
    $("#client-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
}

function openCarrierModal() {
    $("#carrier-admin-form").reset();
    $("#carrier-modal").classList.add("open");
    $("#carrier-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeCarrierModal() {
    $("#carrier-modal").classList.remove("open");
    $("#carrier-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
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

function closePackageTypeModal() {
    $("#package-type-modal").classList.remove("open");
    $("#package-type-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    state.editingPackageTypeId = null;
}

function closeAmountModal() {
    $("#amount-modal").classList.remove("open");
    $("#amount-modal").setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    state.liquidatingEnvioId = null;
}

function resetForm() {
    $("#envio-form").reset();
    state.spec = { tamano: "", peso: "" };
    state.editingId = null;
    $("#envio-db-id").value = "";
    $("#f-dni").value = "";
    $("#f-nombre").value = "";
    $("#f-telefono").value = "";
    $("#f-direccion").value = "";
    $("#f-transportista-id").value = "";
    $("#modal-title").textContent = "Registrar envio";
    if ($("#f-tipo-id")) $("#f-tipo-id").value = "";
    document.querySelectorAll(".field-error").forEach((item) => item.classList.remove("field-error"));
    $("#dni-status").textContent = "";
    $("#transportista-status").textContent = "";
    hideClientForm();
    hideClientNotFound();
    setToday();
}

function setToday() {
    const today = new Date();
    const iso = today.toISOString().slice(0, 10);
    $("#f-fecha").value = iso;
    $("#current-date").textContent = today.toLocaleDateString("es-PE", {
        weekday: "short",
        day: "numeric",
        month: "short",
        year: "numeric",
    });
}

async function saveClient() {
    const payload = clientPayload();

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
        dni: $("#client-admin-dni").value.trim(),
        nombre: $("#client-admin-name").value.trim(),
        telefono: $("#client-admin-phone").value.trim(),
        direccion: $("#client-admin-address").value.trim(),
    };

    try {
        await api.post("/clientes", payload);
        ["#client-admin-dni", "#client-admin-name", "#client-admin-phone", "#client-admin-address"].forEach((selector) => {
            $(selector).value = "";
        });
        closeClientModal();
        showToast("Cliente guardado correctamente.");
        loadManagementLists();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function saveAdminCarrier(event) {
    event.preventDefault();

    const payload = {
        nombre: $("#carrier-admin-name").value.trim(),
        documento: $("#carrier-admin-document").value.trim(),
        telefono: $("#carrier-admin-phone").value.trim(),
    };

    try {
        await api.post("/transportistas", payload);
        ["#carrier-admin-name", "#carrier-admin-document", "#carrier-admin-phone"].forEach((selector) => {
            $(selector).value = "";
        });
        closeCarrierModal();
        showToast("Transportista guardado correctamente.");
        loadManagementLists();
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function searchClient() {
    const input = $("#f-cliente-search");
    const dropdown = $("#client-dropdown");
    const search = input.value.trim();

    if (!search) {
        dropdown.classList.remove("open");
        hideClientNotFound();
        hideClientForm();
        clearSelectedClient();
        $("#dni-status").textContent = "";
        return;
    }

    if (search !== $("#f-nombre").value.trim() && search !== $("#f-dni").value.trim()) {
        clearSelectedClient(false);
    }

    try {
        const response = await api.get(`/clientes?search=${encodeURIComponent(search)}`);
        if (!response.data.length) {
            dropdown.classList.remove("open");
            clearSelectedClient(false);
            showClientNotFound(search);
            return;
        }

        hideClientNotFound();
        dropdown.innerHTML = response.data.map((cliente) => `
            <button type="button" data-dni="${escapeHtml(cliente.dni)}">
                <span>${escapeHtml(cliente.nombre)}</span>
                <small>${escapeHtml(cliente.dni)}</small>
            </button>
        `).join("");
        dropdown.classList.add("open");

        dropdown.querySelectorAll("button").forEach((button) => {
            button.addEventListener("click", () => {
                const cliente = response.data.find((item) => item.dni === button.dataset.dni);
                fillClient(cliente);
                dropdown.classList.remove("open");
            });
        });
    } catch (error) {
        showToast(error.message, "error");
    }
}

function fillClient(cliente) {
    $("#f-dni").value = cliente.dni;
    $("#f-nombre").value = cliente.nombre;
    $("#f-telefono").value = cliente.telefono || "";
    $("#f-direccion").value = cliente.direccion || "";
    $("#f-cliente-search").value = cliente.nombre;
    $("#dni-status").textContent = "Cliente cargado.";
    hideClientNotFound();
    hideClientForm();
}

function clearSelectedClient(clearSearch = true) {
    $("#f-dni").value = "";
    $("#f-nombre").value = "";
    $("#f-telefono").value = "";
    $("#f-direccion").value = "";
    if (clearSearch) {
        $("#f-cliente-search").value = "";
    }
}

function showClientNotFound(search) {
    const looksLikeDni = /^\d{7,12}$/.test(search);
    $("#client-inline-dni").value = looksLikeDni ? search : "";
    $("#client-inline-name").value = looksLikeDni ? "" : search;
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
    ["#client-inline-dni", "#client-inline-name", "#client-inline-phone", "#client-inline-address"].forEach((selector) => {
        $(selector).value = "";
    });
}

async function searchTransportista() {
    const search = $("#f-transportista").value.trim();
    const dropdown = $("#transportista-dropdown");

    try {
        const response = await api.get(`/transportistas?search=${encodeURIComponent(search)}`);
        if (!response.data.length) {
            dropdown.classList.remove("open");
            $("#f-transportista-id").value = "";
            $("#transportista-status").textContent = "Transportista no encontrado. Puedes crearlo abajo.";
            return;
        }

        dropdown.innerHTML = response.data.map((transportista) => `
            <button type="button" data-id="${transportista.id}">
                <span>${escapeHtml(transportista.nombre)}</span>
                <small>${escapeHtml(transportista.documento || "")}</small>
            </button>
        `).join("");
        dropdown.classList.add("open");

        dropdown.querySelectorAll("button").forEach((button) => {
            button.addEventListener("click", () => {
                const transportista = response.data.find((item) => String(item.id) === button.dataset.id);
                fillTransportista(transportista);
                dropdown.classList.remove("open");
            });
        });
    } catch (error) {
        showToast(error.message, "error");
    }
}

function fillTransportista(transportista) {
    $("#f-transportista-id").value = transportista.id;
    $("#f-transportista").value = transportista.nombre;
    $("#transportista-status").textContent = "Transportista cargado.";
}

function renderTipoDropdown(search = "") {
    const dropdown = $("#tipo-dropdown");
    const options = tipos.filter((tipo) => tipo.toLowerCase().includes(search.toLowerCase()));

    if (!options.length) {
        dropdown.classList.remove("open");
        return;
    }

    dropdown.innerHTML = options.map((tipo) => `<button type="button" data-tipo="${tipo}">${tipo}</button>`).join("");
    dropdown.classList.add("open");
    dropdown.querySelectorAll("button").forEach((button) => {
        button.addEventListener("click", () => {
            $("#f-tipo").value = button.dataset.tipo;
            dropdown.classList.remove("open");
            updateDetalle();
        });
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
    $("#f-dni").value = envio.cliente_dni;
    $("#f-nombre").value = envio.cliente;
    $("#f-cliente-search").value = envio.cliente;
    $("#f-telefono").value = envio.telefono || "";
    $("#f-direccion").value = envio.direccion || "";
    $("#f-transportista-id").value = envio.transportista_id || "";
    $("#f-transportista").value = envio.transportista || "";
    $("#f-cantidad").value = envio.cantidad;
    $("#f-tipo").value = envio.tipo;
    $("#f-detalle").value = envio.detalle;
    if ($("#f-guia")) $("#f-guia").value = envio.guia || "";
    $("#f-pago").value = envio.pago;
    if ($("#f-monto")) $("#f-monto").value = envio.monto || "";
    $("#f-obs").value = envio.observacion || "";
    setSpec("tamano", envio.especificacion_tamano);
    setSpec("peso", envio.especificacion_peso);
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
    $("#details-body").innerHTML = `
        <div class="detail-grid">
            <div><span>Fecha</span><strong>${formatDate(envio.fecha)}</strong></div>
            <div><span>Cliente</span><strong>${escapeHtml(envio.cliente)}</strong></div>
            <div><span>DNI</span><strong>${escapeHtml(envio.cliente_dni)}</strong></div>
            <div><span>Telefono</span><strong>${escapeHtml(envio.telefono || "-")}</strong></div>
            <div><span>Direccion</span><strong>${escapeHtml(envio.direccion || "-")}</strong></div>
            <div><span>Transportista</span><strong>${escapeHtml(envio.transportista || "-")}</strong></div>
            <div><span>Cantidad</span><strong>${envio.cantidad}</strong></div>
            <div><span>Tipo</span><strong>${escapeHtml(envio.tipo)}</strong></div>
            <div><span>Guia</span><strong>${escapeHtml(envio.guia || "-")}</strong></div>
            <div><span>Pago</span><strong>${escapeHtml(envio.pago)}</strong></div>
            <div class="span-all"><span>Especificacion</span><strong>${specBadges(envio)}</strong></div>
            <div class="span-all"><span>Detalle</span><p>${escapeHtml(envio.detalle || "-")}</p></div>
            <div class="span-all"><span>Observacion</span><p>${escapeHtml(envio.observacion || "-")}</p></div>
        </div>
    `;
    $("#details-modal").classList.add("open");
    $("#details-modal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function specBadges(envio) {
    const tamano = envio.especificacion_tamano;
    const peso = envio.especificacion_peso;
    const badges = [];

    if (tamano) {
        badges.push(`<span class="badge spec-size-${slug(tamano)}">${escapeHtml(tamano)}</span>`);
    }
    if (peso) {
        badges.push(`<span class="badge spec-weight-${slug(peso)}">${escapeHtml(peso)}</span>`);
    }

    return badges.join(" ") || '<span class="cell-muted">-</span>';
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

function setSpec(group, value) {
    state.spec[group] = value || "";
    const field = group === "tamano" ? $("#f-tamano") : $("#f-peso");
    field.value = value || "";
}

function updateDetalle() {
    const cantidad = $("#f-cantidad").value.trim();
    const tipo = $("#f-tipo").value.trim();
    const tamano = $("#f-tamano").value;
    const peso = $("#f-peso").value;
    const parts = [];

    if (cantidad) parts.push(cantidad);
    if (tipo) parts.push(tipo.toLowerCase() + (Number(cantidad) > 1 ? "s" : ""));

    state.spec.tamano = tamano;
    state.spec.peso = peso;

    const specs = [tamano, peso].filter(Boolean);
    if (specs.length) {
        parts.push(`(${specs.map((item) => item.toLowerCase()).join(", ")})`);
    }

    if (parts.length) {
        $("#f-detalle").value = `${parts.join(" ")}.`;
    } else {
        $("#f-detalle").value = "";
    }
}

function envioPayload() {
    return {
        codigo: $("#f-codigo").value,
        fecha: $("#f-fecha").value,
        cliente_dni: $("#f-dni").value.trim(),
        cliente: $("#f-nombre").value.trim(),
        telefono: $("#f-telefono").value.trim(),
        direccion: $("#f-direccion").value.trim(),
        transportista_id: $("#f-transportista-id").value || null,
        cantidad: $("#f-cantidad").value.trim(),
        tipo: $("#f-tipo").value.trim(),
        especificacion_tamano: state.spec.tamano,
        especificacion_peso: state.spec.peso,
        detalle: $("#f-detalle").value.trim(),
        guia: $("#f-guia") ? $("#f-guia").value.trim() : null,
        pago: $("#f-pago").value,
        monto: $("#f-monto") ? $("#f-monto").value : null,
        observacion: $("#f-obs").value.trim(),
    };
}

function clientPayload() {
    return {
        dni: $("#client-inline-dni").value.trim(),
        nombre: $("#client-inline-name").value.trim(),
        telefono: $("#client-inline-phone").value.trim(),
        direccion: $("#client-inline-address").value.trim(),
    };
}

function validate(payload) {
    let valid = true;
    const required = {
        "#f-cliente-search": payload.cliente_dni && payload.cliente,
        "#f-transportista": payload.transportista_id,
        "#f-cantidad": payload.cantidad,
        "#f-tipo": payload.tipo,
        "#f-detalle": payload.detalle,
        "#f-pago": payload.pago,
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

    return valid;
}

function clearFilters() {
    state.filters = {
        q: "",
        pago: "",
        fecha_desde: "",
        fecha_hasta: "",
        cliente: "",
        transportista: "",
        tipo: "",
    };
    $("#search-input").value = "";
    $("#filter-pago").value = "";
    $("#filter-fecha-desde").value = "";
    $("#filter-fecha-hasta").value = "";
    $("#filter-cliente").value = "";
    $("#filter-transportista").value = "";
    $("#filter-tipo").value = "";
    loadEnvios();
}

function exportEnvios() {
    window.location.href = `/api/envios/export?${filterParams().toString()}`;
}

async function loadManagementLists() {
    try {
        const [clientes, transportistas] = await Promise.all([
            api.get("/clientes"),
            api.get("/transportistas"),
        ]);
        renderClientsTable(clientes.data);
        renderCarriersTable(transportistas.data);
    } catch (error) {
        showToast(error.message, "error");
    }
}

function renderClientsTable(items) {
    const list = $("#clients-list");
    if (!items.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="4">Sin clientes registrados.</td></tr>`;
        return;
    }

    list.innerHTML = items.map((cliente) => `
        <tr>
            <td class="cell-code">${escapeHtml(cliente.dni)}</td>
            <td><strong>${escapeHtml(cliente.nombre)}</strong></td>
            <td class="cell-muted">${escapeHtml(cliente.telefono || "-")}</td>
            <td class="cell-muted">${escapeHtml(cliente.direccion || "-")}</td>
        </tr>
    `).join("");
}

function renderCarriersTable(items) {
    const list = $("#carriers-list");
    if (!items.length) {
        list.innerHTML = `<tr><td class="empty-state" colspan="4">Sin transportistas registrados.</td></tr>`;
        return;
    }

    list.innerHTML = items.map((transportista) => `
        <tr>
            <td><strong>${escapeHtml(transportista.nombre)}</strong></td>
            <td class="cell-code">${escapeHtml(transportista.documento || "-")}</td>
            <td class="cell-muted">${escapeHtml(transportista.telefono || "-")}</td>
            <td><span class="badge pagado">${transportista.activo ? "Activo" : "Inactivo"}</span></td>
        </tr>
    `).join("");
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
                    <button class="button icon-only icon-edit" type="button" data-role-edit="${role.id}" title="Editar" aria-label="Editar rol"></button>
                    <button class="button button-danger icon-only icon-delete" type="button" data-role-delete="${role.id}" title="Eliminar" aria-label="Eliminar rol"></button>
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
                    <button class="button icon-only icon-edit" type="button" data-user-edit="${user.id}" title="Editar" aria-label="Editar usuario"></button>
                    <button class="button button-danger icon-only icon-delete" type="button" data-user-delete="${user.id}" title="Eliminar" aria-label="Eliminar usuario"></button>
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

function truncate(value, size) {
    if (!value || value.length <= size) return value || "";
    return `${value.slice(0, size)}...`;
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
