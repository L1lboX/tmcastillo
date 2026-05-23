<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Transportes Castillo - Sistema de Envíos</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @php
        $appUser = [
            'id' => auth()->id(),
            'name' => auth()->user()->name,
            'username' => auth()->user()->username,
            'permissions' => auth()->user()->permissionNames(),
            'roles' => auth()->user()->roles->pluck('name')->values(),
        ];
    @endphp
    <script>
        window.AppContext = { user: {{ Illuminate\Support\Js::from($appUser) }} };
    </script>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true">TC</div>
                <div>
                    <strong>Transportes Castillo</strong>
                    <span>Gestión operativa</span>
                </div>
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-expanded="false" aria-controls="sidebar-nav" aria-label="Abrir menú">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

            <nav class="sidebar-nav" id="sidebar-nav" aria-label="Módulos">
                @can('envios.view')
                    <button class="sidebar-link active" type="button" data-section="envios">Envíos</button>
                @endcan
                @can('clientes.manage')
                    <button class="sidebar-link" type="button" data-section="clientes">Clientes</button>
                @endcan
                @can('transportistas.manage')
                    <button class="sidebar-link" type="button" data-section="transportistas">Transportistas</button>
                @endcan
                @can('tipos_paquete.manage')
                    <button class="sidebar-link" type="button" data-section="tipos">Tipos de paquete</button>
                @endcan
                @can('envios.amounts')
                    <button class="sidebar-link" type="button" data-section="montos">Montos</button>
                @endcan
                @can('clientes.debt')
                    <button class="sidebar-link" type="button" data-section="cuentas">Cuentas por cobrar</button>
                @endcan
                @can('dashboard.view')
                    <button class="sidebar-link" type="button" data-section="dashboard">Dashboard</button>
                @endcan
                @can('roles.manage')
                    <button class="sidebar-link" type="button" data-section="roles">Roles</button>
                @endcan
                @can('users.manage')
                    <button class="sidebar-link" type="button" data-section="usuarios">Usuarios</button>
                @endcan
            </nav>

            <div class="sidebar-footer">
                <div class="user-chip-sidebar">
                    <span>{{ auth()->user()->name }}</span>
                    <small>{{ auth()->user()->roles->pluck('label')->join(', ') }}</small>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button button-logout" type="submit">Salir</button>
                </form>
            </div>
        </aside>
        <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

        <div class="app-main">
            <header class="topbar">
                <div>
                    <strong id="section-title">Envíos</strong>
                    <span id="section-subtitle">Registro y control de carga</span>
                </div>
                <button class="sidebar-toggle" id="topbar-toggle" type="button" aria-expanded="false" aria-label="Abrir menú">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="topbar-metrics">
                    <div class="metric">
                        <span id="stat-total">0</span>
                        <small>Total</small>
                    </div>
                    <div class="metric">
                        <span id="stat-hoy">0</span>
                        <small>Hoy</small>
                    </div>
                    <div class="metric">
                        <span id="stat-ce">0</span>
                        <small>Contra entrega</small>
                    </div>
                    @can('envios.amounts')
                        <div class="metric">
                            <span id="stat-pendientes">0</span>
                            <small>Pend. monto</small>
                        </div>
                    @endcan
                    <time id="current-date"></time>
                </div>
            </header>

            <main class="workspace">
                <section class="module-page" data-page="envios">
                    <section class="page-head page-head-compact">
                        <div class="head-actions">
                            @can('envios.export')
                                <button class="button button-with-icon icon-download" id="btn-export" type="button">Exportar Excel</button>
                            @endcan
                            @can('envios.create')
                                <button class="button button-primary button-with-icon icon-add" id="btn-open-modal" type="button">Nuevo envío</button>
                            @endcan
                        </div>
                    </section>

                    <section class="table-shell">
                        <div class="table-tools table-tools-bar">
                            <div class="search-group">
                                <label class="search-field">
                                    <span>Buscar</span>
                                    <input id="search-input" type="search" placeholder="Cliente, DNI, guía o tipo">
                                </label>
                                <button class="button button-with-icon icon-filter" id="btn-toggle-filters" type="button">Filtros</button>
                            </div>
                            <strong id="count-display">0 registros</strong>
                        </div>

                        <div class="filter-panel" id="filter-panel">
                            <div class="table-tools">
                                <div class="tool-group">
                                    <label>
                                        <span>Desde</span>
                                        <input id="filter-fecha-desde" type="date">
                                    </label>
                                    <label>
                                        <span>Hasta</span>
                                        <input id="filter-fecha-hasta" type="date">
                                    </label>
                                    <label>
                                        <span>Pago</span>
                                        <select id="filter-pago">
                                            <option value="">Todos</option>
                                            <option value="Pendiente">Pendiente</option>
                                            <option value="Pagado">Pagado</option>
                                            <option value="Contra Entrega">Contra entrega</option>
                                            <option value="Credito">Credito</option>
                                        </select>
                                    </label>
                                </div>
                            </div>

                            <div class="table-tools table-tools-secondary">
                                <div class="tool-group">
                                    <label>
                                        <span>Cliente</span>
                                        <input id="filter-cliente" type="search" placeholder="Nombre o DNI">
                                    </label>
                                    <label>
                                        <span>Transportista</span>
                                        <input id="filter-transportista" type="search" placeholder="Nombre o documento">
                                    </label>
                                    <label>
                                        <span>Tipo</span>
                                        <input id="filter-tipo" type="search" placeholder="Caja, saco...">
                                    </label>
                                    <button class="button button-with-icon icon-clear" id="btn-clear-filters" type="button">Limpiar filtros</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th class="col-hide-mob">Transportista</th>
                                        <th class="col-hide-mob">Cant.</th>
                                        <th class="col-hide-mob">Tipo</th>
                                        <th class="col-hide-mob">Especificacion</th>
                                        <th class="col-hide-mob">Guia</th>
                                        <th class="col-hide-mob">Pago</th>
                                        @can('envios.amounts')
                                            <th class="col-hide-mob">Monto</th>
                                        @endcan
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="envios-tbody"></tbody>
                            </table>
                        </div>

                        <div id="pagination-controls"></div>
                    </section>
                </section>

                <section class="module-page hidden" data-page="clientes">
                    <section class="page-head">
                        <div>
                            <h1>Clientes</h1>
                            <p>Registro independiente de clientes para usar en envíos.</p>
                        </div>
                        @can('clientes.manage')
                            <button class="button button-primary button-with-icon icon-add-user" id="btn-open-client-modal" type="button">Nuevo cliente</button>
                        @endcan
                    </section>

                    <section class="table-shell">
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>DNI</th>
                                        <th>Cliente</th>
                                        <th>Telefono</th>
                                        <th>Direccion</th>
                                    </tr>
                                </thead>
                                <tbody id="clients-list"></tbody>
                            </table>
                        </div>
                    </section>
                </section>

                <section class="module-page hidden" data-page="transportistas">
                    <section class="page-head">
                        <div>
                            <h1>Transportistas</h1>
                            <p>Responsables disponibles para asignar paquetes.</p>
                        </div>
                        @can('transportistas.manage')
                            <button class="button button-primary button-with-icon icon-truck-add" id="btn-open-carrier-modal" type="button">Nuevo transportista</button>
                        @endcan
                    </section>

                    <section class="table-shell">
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Documento</th>
                                        <th>Telefono</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="carriers-list"></tbody>
                            </table>
                        </div>
                    </section>
                </section>

                <section class="module-page hidden" data-page="tipos">
                    <section class="page-head">
                        <div>
                            <h1>Tipos de paquete</h1>
                            <p>Precio que se paga al transportista por unidad.</p>
                        </div>
                        <button class="button button-primary button-with-icon icon-add" id="btn-open-package-type-modal" type="button">Nuevo tipo</button>
                    </section>

                    <section class="table-shell">
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Precio transportista</th>
                                        <th>Descripcion</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="package-types-list"></tbody>
                            </table>
                        </div>
                    </section>
                </section>

                <section class="module-page hidden" data-page="montos">
                    <section class="page-head">
                        <div>
                            <h1>Envios pendientes de monto</h1>
                            <p>Completa el monto cobrado, pago y margen del envío.</p>
                        </div>
                    </section>

                    <section class="table-shell">
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Transportista</th>
                                        <th>Cant.</th>
                                        <th>Tipo</th>
                                        <th>Costo transportista</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="pending-amounts-list"></tbody>
                            </table>
                        </div>
                    </section>
                </section>

                <section class="module-page hidden" data-page="cuentas">
                    <section class="page-head">
                        <div>
                            <h1>Cuentas por cobrar</h1>
                            <p>Control de deudas y abonos de clientes.</p>
                        </div>
                    </section>

                    <section class="stats-row" id="debt-stats" style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                        <div class="metric-card" style="flex: 1; min-width: 200px; background: #1e293b; border-radius: 0.5rem; padding: 1rem;">
                            <small style="color: #94a3b8;">Total clientes con deuda</small>
                            <strong id="debt-client-count" style="font-size: 1.5rem; display: block; margin-top: 0.25rem;">0</strong>
                        </div>
                        <div class="metric-card" style="flex: 1; min-width: 200px; background: #1e293b; border-radius: 0.5rem; padding: 1rem;">
                            <small style="color: #94a3b8;">Deuda total pendiente</small>
                            <strong id="debt-total-amount" style="font-size: 1.5rem; display: block; margin-top: 0.25rem;">S/ 0.00</strong>
                        </div>
                    </section>

                    <section class="table-shell">
                        <div class="table-tools">
                            <label class="search-field">
                                <span>Buscar cliente</span>
                                <input id="debt-client-search" type="search" placeholder="Nombre o DNI">
                            </label>
                            <button class="button button-primary button-with-icon icon-add" id="btn-open-abono-modal" type="button">Registrar abono</button>
                        </div>
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>DNI</th>
                                        <th>Cliente</th>
                                        <th>Telefono</th>
                                        <th>Saldo pendiente</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="debt-clients-list"></tbody>
                            </table>
                        </div>
                    </section>

                    <section class="table-shell" style="margin-top: 1.5rem;">
                        <div class="table-tools">
                            <strong id="debt-movements-title">Movimientos del cliente</strong>
                        </div>
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Envio</th>
                                        <th>Monto</th>
                                        <th>Saldo acumulado</th>
                                        <th>Observacion</th>
                                    </tr>
                                </thead>
                                <tbody id="debt-movements-list"></tbody>
                            </table>
                        </div>
                    </section>
                </section>

                <section class="module-page hidden" data-page="dashboard">
                    <section class="placeholder-module">
                        <h1>Dashboard</h1>
                        <p>Módulo reservado para indicadores, ganancias y análisis por rol.</p>
                    </section>
                </section>

                <section class="module-page hidden" data-page="roles">
                    <section class="page-head">
                        <div>
                            <h1>Roles y permisos</h1>
                            <p>Define qué puede ver, crear, editar, eliminar o registrar cada rol.</p>
                        </div>
                        @can('roles.manage')
                            <button class="button button-primary button-with-icon icon-add" id="btn-open-role-modal" type="button">Nuevo rol</button>
                        @endcan
                    </section>

                    <section class="table-shell">
                        <div class="table-tools">
                            <label class="search-field">
                                <span>Buscar rol</span>
                                <input id="role-search-input" type="search" placeholder="Agente, contador, admin...">
                            </label>
                        </div>
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Rol</th>
                                        <th>Permisos</th>
                                        <th>Fecha</th>
                                        <th>Usuarios</th>
                                        <th>Op</th>
                                    </tr>
                                </thead>
                                <tbody id="roles-list"></tbody>
                            </table>
                        </div>
                    </section>
                </section>

                <section class="module-page hidden" data-page="usuarios">
                    <section class="page-head">
                        <div>
                            <h1>Usuarios</h1>
                            <p>Asigna un rol operativo a cada usuario del sistema.</p>
                        </div>
                        @can('users.manage')
                            <button class="button button-primary button-with-icon icon-add-user" id="btn-open-user-modal" type="button">Nuevo usuario</button>
                        @endcan
                    </section>

                    <section class="table-shell">
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Roles</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="users-list"></tbody>
                            </table>
                        </div>
                    </section>
                </section>
            </main>
        </div>
    </div>

    <div class="modal-backdrop" id="details-modal" aria-hidden="true">
        <section class="modal modal-small" role="dialog" aria-modal="true" aria-labelledby="details-title">
            <header class="modal-head">
                <h2 id="details-title">Detalle del envío</h2>
                <button class="button button-with-icon icon-close" id="btn-close-details-modal" type="button">Cerrar</button>
            </header>
            <div class="modal-body details-body" id="details-body"></div>
        </section>
    </div>

    <div class="modal-backdrop" id="client-modal" aria-hidden="true">
        <section class="modal modal-small" role="dialog" aria-modal="true" aria-labelledby="client-modal-title">
            <header class="modal-head">
                <h2 id="client-modal-title">Registrar cliente</h2>
                <button class="button button-with-icon icon-close" id="btn-close-client-modal" type="button">Cerrar</button>
            </header>

            <form id="client-admin-form" class="modal-body">
                <div class="form-grid two">
                    <label>
                        <span>DNI</span>
                        <input id="client-admin-dni" type="text" maxlength="12" placeholder="DNI">
                    </label>
                    <label>
                        <span>Nombre</span>
                        <input id="client-admin-name" type="text" placeholder="Nombre completo">
                    </label>
                    <label>
                        <span>Telefono</span>
                        <input id="client-admin-phone" type="text" maxlength="20" placeholder="987654321">
                    </label>
                    <label>
                        <span>Direccion</span>
                        <input id="client-admin-address" type="text" placeholder="Ciudad o direccion">
                    </label>
                </div>
                <footer class="modal-actions">
                    <button class="button button-with-icon icon-cancel" id="btn-cancel-client-modal" type="button">Cancelar</button>
                    <button class="button button-primary button-with-icon icon-save" type="submit">Guardar cliente</button>
                </footer>
            </form>
        </section>
    </div>

    <div class="modal-backdrop" id="carrier-modal" aria-hidden="true">
        <section class="modal modal-small" role="dialog" aria-modal="true" aria-labelledby="carrier-modal-title">
            <header class="modal-head">
                <h2 id="carrier-modal-title">Registrar transportista</h2>
                <button class="button button-with-icon icon-close" id="btn-close-carrier-modal" type="button">Cerrar</button>
            </header>

            <form id="carrier-admin-form" class="modal-body">
                <div class="form-grid two">
                    <label>
                        <span>Nombre</span>
                        <input id="carrier-admin-name" type="text" placeholder="Nombre completo">
                    </label>
                    <label>
                        <span>Documento</span>
                        <input id="carrier-admin-document" type="text" maxlength="20" placeholder="DNI o codigo">
                    </label>
                    <label class="span-2">
                        <span>Telefono</span>
                        <input id="carrier-admin-phone" type="text" maxlength="20" placeholder="987654321">
                    </label>
                </div>
                <footer class="modal-actions">
                    <button class="button button-with-icon icon-cancel" id="btn-cancel-carrier-modal" type="button">Cancelar</button>
                    <button class="button button-primary button-with-icon icon-save" type="submit">Guardar transportista</button>
                </footer>
            </form>
        </section>
    </div>

    <div class="modal-backdrop" id="envio-modal" aria-hidden="true">
        <section class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <header class="modal-head">
                <h2 id="modal-title">Registrar envío</h2>
                <button class="button button-with-icon icon-close" id="btn-close-modal" type="button">Cerrar</button>
            </header>

            <form id="envio-form" class="modal-body">
                <input type="hidden" id="envio-db-id">

                <div class="form-section">
                    <h3>01 Fecha</h3>
                    <div class="form-grid two">
                        <label>
                            <span>Fecha</span>
                            <input type="date" id="f-fecha" readonly>
                        </label>
                        <label>
                            <span>ID</span>
                            <input type="text" id="f-codigo" readonly>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>02 Cliente</h3>
                    <input type="hidden" id="f-dni">
                    <input type="hidden" id="f-nombre">
                    <input type="hidden" id="f-telefono">
                    <input type="hidden" id="f-direccion">

                    <div class="client-lookup">
                        <label class="field-relative">
                            <span>Cliente / busqueda</span>
                            <input type="text" id="f-cliente-search" autocomplete="off" placeholder="Buscar por DNI o nombre">
                            <div class="dropdown" id="client-dropdown"></div>
                        </label>
                        <button class="button button-soft button-with-icon icon-add-user hidden" id="btn-show-client-form" type="button">Registrar cliente</button>
                    </div>

                    <div class="client-alert hidden" id="client-not-found">Cliente no encontrado. Registralo para continuar.</div>
                    <small id="dni-status"></small>

                    <div class="client-inline-form hidden" id="client-inline-form">
                        <div class="form-grid four">
                            <label>
                                <span>DNI</span>
                                <input type="text" id="client-inline-dni" maxlength="12" placeholder="DNI">
                            </label>
                            <label class="span-3">
                                <span>Nombre completo</span>
                                <input type="text" id="client-inline-name" placeholder="Nombre del cliente">
                            </label>
                            <label class="span-2">
                            <span>Telefono</span>
                                <input type="text" id="client-inline-phone" maxlength="9" placeholder="987654321">
                            </label>
                            <label class="span-2">
                                <span>Direccion / destino</span>
                                <input type="text" id="client-inline-address" placeholder="Ciudad o direccion">
                            </label>
                        </div>
                        <button class="button button-soft button-with-icon icon-save" id="btn-save-client" type="button">Guardar cliente</button>
                    </div>
                </div>

                <div class="form-section">
                    <h3>03 Transportista</h3>
                    <div class="form-grid two">
                        <label class="span-2 field-relative">
                            <span>Transportista / busqueda</span>
                            <input type="hidden" id="f-transportista-id">
                            <input type="text" id="f-transportista" autocomplete="off" placeholder="Buscar transportista por nombre">
                            <div class="dropdown" id="transportista-dropdown"></div>
                            <small id="transportista-status"></small>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>04 Datos del envío</h3>
                    <div class="form-grid four">
                        <label>
                            <span>Cantidad</span>
                            <input type="text" id="f-cantidad" inputmode="numeric" placeholder="1">
                        </label>
                        <label class="span-3 field-relative">
                            <span>Tipo de paquete</span>
                            <input type="text" id="f-tipo" autocomplete="off" placeholder="Caja, saco, paquete...">
                            <div class="dropdown" id="tipo-dropdown"></div>
                        </label>
                    </div>
                </div>

                <div class="form-section spec-layout">
                    <h3>05 Especificación</h3>
                    <label>
                        <span>Tamaño</span>
                        <select id="f-tamano">
                            <option value="">Seleccionar</option>
                            <option value="Pequeno">Pequeno</option>
                            <option value="Mediano">Mediano</option>
                            <option value="Grande">Grande</option>
                            <option value="Extra grande">Extra grande</option>
                        </select>
                    </label>
                    <label>
                        <span>Peso</span>
                        <select id="f-peso">
                            <option value="">Seleccionar</option>
                            <option value="Liviano">Liviano</option>
                            <option value="Moderado">Moderado</option>
                            <option value="Pesado">Pesado</option>
                            <option value="Muy pesado">Muy pesado</option>
                        </select>
                    </label>
                </div>

                <div class="form-section">
                    <h3>06 Detalle</h3>
                    <label>
                        <span>Detalle del envío</span>
                        <textarea id="f-detalle" rows="3" placeholder="Se genera automáticamente y puedes editarlo"></textarea>
                    </label>
                </div>

                <div class="form-section">
                    <h3>07 Guía</h3>
                    <div class="form-grid two">
                        <label>
                            <span>Guía de remitente</span>
                            <input type="text" id="f-guia" maxlength="40" inputmode="text" autocomplete="off" placeholder="ABC123456">
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>08 Observación</h3>
                    <label>
                        <span>Observaciones</span>
                        <textarea id="f-obs" rows="2" placeholder="Notas internas del envio"></textarea>
                    </label>
                </div>

                <footer class="modal-actions">
                    <button class="button button-with-icon icon-cancel" id="btn-cancel" type="button">Cancelar</button>
                    <button class="button button-primary button-with-icon icon-save" type="submit">Guardar envío</button>
                </footer>
            </form>
        </section>
    </div>

    @can('users.manage')
        <div class="modal-backdrop" id="user-modal" aria-hidden="true">
            <section class="modal modal-small" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
                <header class="modal-head">
                    <h2 id="user-modal-title">Registrar usuario</h2>
                    <button class="button button-with-icon icon-close" id="btn-close-user-modal" type="button">Cerrar</button>
                </header>

                <form id="user-form" class="modal-body">
                    <input type="hidden" id="user-id">
                    <div class="form-grid two">
                        <label>
                            <span>Nombre</span>
                            <input id="user-name" type="text" placeholder="Nombre completo">
                        </label>
                        <label>
                            <span>Usuario</span>
                            <input id="user-username" type="text" placeholder="usuario">
                        </label>
                        <label>
                            <span>Email</span>
                            <input id="user-email" type="email" placeholder="correo@dominio.com">
                        </label>
                        <label>
                            <span>Contrasena</span>
                            <input id="user-password" type="password" placeholder="Minimo 8 caracteres">
                        </label>
                        <label class="span-2">
                            <span>Rol</span>
                            <select id="user-role"></select>
                        </label>
                        <label class="check-row span-2">
                            <input id="user-active" type="checkbox" checked>
                            <span>Usuario activo</span>
                        </label>
                    </div>
                    <footer class="modal-actions">
                        <button class="button button-with-icon icon-cancel" id="btn-cancel-user-modal" type="button">Cancelar</button>
                        <button class="button button-primary button-with-icon icon-save" id="btn-save-user" type="button">Guardar usuario</button>
                    </footer>
                </form>
            </section>
        </div>
    @endcan

    @can('roles.manage')
        <div class="modal-backdrop" id="role-modal" aria-hidden="true">
            <section class="modal" role="dialog" aria-modal="true" aria-labelledby="role-modal-title">
                <header class="modal-head">
                    <h2 id="role-modal-title">Registrar rol</h2>
                    <button class="button button-with-icon icon-close" id="btn-close-role-modal" type="button">Cerrar</button>
                </header>

                <form id="role-form" class="modal-body">
                    <input type="hidden" id="role-id">
                    <div class="form-grid two">
                        <label>
                            <span>Rol</span>
                            <input id="role-label" type="text" placeholder="Agente de envios">
                        </label>
                        <label>
                            <span>Identificador</span>
                            <input id="role-name" type="text" placeholder="agente_envios">
                        </label>
                    </div>
                    <section class="permissions-box">
                        <div class="permissions-head">
                            <strong>Permisos</strong>
                            <small>Para que el agente no vea montos, deja desmarcado `envios.amounts`.</small>
                        </div>
                        <div class="permissions-grid" id="role-permissions"></div>
                    </section>
                    <footer class="modal-actions">
                        <button class="button button-with-icon icon-cancel" id="btn-cancel-role-modal" type="button">Cancelar</button>
                        <button class="button button-primary button-with-icon icon-save" type="submit">Guardar rol</button>
                    </footer>
                </form>
            </section>
        </div>
    @endcan

    @can('tipos_paquete.manage')
        <div class="modal-backdrop" id="package-type-modal" aria-hidden="true">
            <section class="modal modal-small" role="dialog" aria-modal="true" aria-labelledby="package-type-modal-title">
                <header class="modal-head">
                    <h2 id="package-type-modal-title">Registrar tipo</h2>
                    <button class="button button-with-icon icon-close" id="btn-close-package-type-modal" type="button">Cerrar</button>
                </header>

                <form id="package-type-form" class="modal-body">
                    <input type="hidden" id="package-type-id">
                    <div class="form-grid two">
                        <label>
                            <span>Tipo</span>
                            <input id="package-type-name" type="text" placeholder="Caja">
                        </label>
                        <label>
                            <span>Precio transportista</span>
                            <input id="package-type-price" type="number" min="0" step="0.01" placeholder="5.00">
                        </label>
                        <label class="span-2">
                            <span>Descripcion</span>
                            <input id="package-type-description" type="text" placeholder="Detalle interno">
                        </label>
                        <label class="check-row span-2">
                            <input id="package-type-active" type="checkbox" checked>
                            <span>Activo</span>
                        </label>
                    </div>
                    <footer class="modal-actions">
                        <button class="button button-with-icon icon-cancel" id="btn-cancel-package-type-modal" type="button">Cancelar</button>
                        <button class="button button-primary button-with-icon icon-save" type="submit">Guardar tipo</button>
                    </footer>
                </form>
            </section>
        </div>
    @endcan

    @can('envios.amounts')
        <div class="modal-backdrop" id="amount-modal" aria-hidden="true">
            <section class="modal modal-small" role="dialog" aria-modal="true" aria-labelledby="amount-modal-title">
                <header class="modal-head">
                    <h2 id="amount-modal-title">Registrar monto</h2>
                    <button class="button button-with-icon icon-close" id="btn-close-amount-modal" type="button">Cerrar</button>
                </header>

                <form id="amount-form" class="modal-body">
                    <input type="hidden" id="amount-envio-id">
                    <div class="amount-summary" id="amount-summary"></div>
                    <div class="form-grid two">
                        <label>
                            <span>Monto cobrado</span>
                            <input id="amount-total" type="number" min="0" step="0.01" placeholder="150.00">
                        </label>
                        <label>
                            <span>Estado de pago</span>
                            <select id="amount-payment">
                                <option value="Pagado">Pagado</option>
                                <option value="Contra Entrega">Contra entrega</option>
                                <option value="Credito">Credito</option>
                            </select>
                        </label>
                    </div>
                    <footer class="modal-actions">
                        <button class="button button-with-icon icon-cancel" id="btn-cancel-amount-modal" type="button">Cancelar</button>
                        <button class="button button-primary button-with-icon icon-save" type="submit">Guardar monto</button>
                    </footer>
                </form>
            </section>
        </div>
    @endcan

    @can('clientes.debt')
        <div class="modal-backdrop" id="abono-modal" aria-hidden="true">
            <section class="modal modal-small" role="dialog" aria-modal="true" aria-labelledby="abono-modal-title">
                <header class="modal-head">
                    <h2 id="abono-modal-title">Registrar abono</h2>
                    <button class="button button-with-icon icon-close" id="btn-close-abono-modal" type="button">Cerrar</button>
                </header>

                <form id="abono-form" class="modal-body">
                    <div class="amount-summary" id="abono-summary"></div>
                    <div class="form-grid two">
                        <label class="span-2">
                            <span>Cliente</span>
                            <input id="abono-cliente-dni" type="hidden">
                            <input id="abono-cliente-nombre" type="text" readonly placeholder="Cliente seleccionado">
                        </label>
                        <label>
                            <span>Monto abono</span>
                            <input id="abono-monto" type="number" min="0.01" step="0.01" placeholder="100.00">
                        </label>
                        <label>
                            <span>Fecha</span>
                            <input id="abono-fecha" type="date">
                        </label>
                        <label class="span-2">
                            <span>Observacion</span>
                            <input id="abono-observacion" type="text" placeholder="Nota opcional">
                        </label>
                    </div>
                    <footer class="modal-actions">
                        <button class="button button-with-icon icon-cancel" id="btn-cancel-abono-modal" type="button">Cancelar</button>
                        <button class="button button-primary button-with-icon icon-save" type="submit">Guardar abono</button>
                    </footer>
                </form>
            </section>
        </div>
    @endcan

    <div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>
