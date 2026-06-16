<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Asignación de Carga - Transportes Castillo</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    @vite(['resources/css/app.css', 'resources/css/asignacion.css', 'resources/js/app.js', 'resources/js/asignacion/index.jsx'])
</head>
<body>
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
                @can('dashboard.view')
                    <div class="sidebar-group" data-group="admin">
                        <button class="sidebar-group-label" type="button" data-toggle-group="admin">
                            <span>Administración</span>
                            <span class="material-icon sidebar-group-arrow">expand_more</span>
                        </button>
                        <a class="sidebar-link" href="{{ route('sistema') }}">Dashboard</a>
                        @can('roles.manage')
                            <a class="sidebar-link" href="{{ route('sistema') }}">Roles</a>
                        @endcan
                        @can('users.manage')
                            <a class="sidebar-link" href="{{ route('sistema') }}">Usuarios</a>
                        @endcan
                    </div>
                @endcan

                <div class="sidebar-group" data-group="encomiendas">
                    <button class="sidebar-group-label" type="button" data-toggle-group="encomiendas">
                        <span>Encomiendas</span>
                        <span class="material-icon sidebar-group-arrow">expand_more</span>
                    </button>
                    @can('envios.view')
                        <a class="sidebar-link" href="{{ route('sistema') }}">Envíos</a>
                    @endcan
                    @can('envios.create')
                        <button class="sidebar-link active" type="button">Asignación de Carga</button>
                    @endcan
                </div>

                @can('envios.amounts')
                    <div class="sidebar-group" data-group="finanzas">
                        <button class="sidebar-group-label" type="button" data-toggle-group="finanzas">
                            <span>Finanzas</span>
                            <span class="material-icon sidebar-group-arrow">expand_more</span>
                        </button>
                        <a class="sidebar-link" href="{{ route('sistema') }}">Montos</a>
                        @can('clientes.debt')
                            <a class="sidebar-link" href="{{ route('sistema') }}">Cuentas por cobrar</a>
                        @endcan
                    </div>
                @elseif(auth()->user()->can('clientes.debt'))
                    <div class="sidebar-group" data-group="finanzas">
                        <button class="sidebar-group-label" type="button" data-toggle-group="finanzas">
                            <span>Finanzas</span>
                            <span class="material-icon sidebar-group-arrow">expand_more</span>
                        </button>
                        @can('clientes.debt')
                            <a class="sidebar-link" href="{{ route('sistema') }}">Cuentas por cobrar</a>
                        @endcan
                    </div>
                @endcan

                <div class="sidebar-group" data-group="registros">
                    <button class="sidebar-group-label" type="button" data-toggle-group="registros">
                        <span>Registros</span>
                        <span class="material-icon sidebar-group-arrow">expand_more</span>
                    </button>
                    @can('clientes.manage')
                        <a class="sidebar-link" href="{{ route('sistema') }}">Clientes</a>
                    @endcan
                    @can('transportistas.manage')
                        <a class="sidebar-link" href="{{ route('sistema') }}">Transportistas</a>
                    @endcan
                </div>
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

        <main class="app-main">
            <div id="asignacion-root"></div>
        </main>
    </div>
</body>
</html>
