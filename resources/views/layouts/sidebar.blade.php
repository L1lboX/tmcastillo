<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark" aria-hidden="true">TC</div>
        <div>
            <strong>Transportes Castillo</strong>
            <span>Gestión operativa</span>
        </div>
        <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-expanded="false" aria-controls="sidebar-nav" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>
    </div>

    <nav class="sidebar-nav" id="sidebar-nav" aria-label="Módulos">
        @can('dashboard.view')
            <div class="sidebar-group" data-group="admin">
                <button class="sidebar-group-label" type="button" data-toggle-group="admin">
                    <span>Administración</span>
                    <span class="material-icon sidebar-group-arrow">expand_more</span>
                </button>
                <a class="sidebar-link {{ request()->query('section') === 'dashboard' ? 'active' : '' }}" href="{{ route('sistema', ['section' => 'dashboard']) }}">Dashboard</a>
                @can('roles.manage')
                    <a class="sidebar-link {{ request()->query('section') === 'roles' ? 'active' : '' }}" href="{{ route('sistema', ['section' => 'roles']) }}">Roles</a>
                @endcan
                @can('users.manage')
                    <a class="sidebar-link {{ request()->query('section') === 'usuarios' ? 'active' : '' }}" href="{{ route('sistema', ['section' => 'usuarios']) }}">Usuarios</a>
                @endcan
            </div>
        @endcan

        <div class="sidebar-group" data-group="encomiendas">
            <button class="sidebar-group-label" type="button" data-toggle-group="encomiendas">
                <span>Encomiendas</span>
                <span class="material-icon sidebar-group-arrow">expand_more</span>
            </button>
            @can('envios.view')
                <a class="sidebar-link {{ (request()->routeIs('sistema') && !request()->has('section')) || request()->query('section') === 'envios' ? 'active' : '' }}" href="{{ route('sistema', ['section' => 'envios']) }}">Envíos</a>
            @endcan
            @can('envios.create')
                <a class="sidebar-link {{ request()->routeIs('asignacion') ? 'active' : '' }}" href="{{ route('asignacion') }}">Asignación de Carga</a>
            @endcan
        </div>

        @can('envios.amounts')
            <div class="sidebar-group" data-group="finanzas">
                <button class="sidebar-group-label" type="button" data-toggle-group="finanzas">
                    <span>Finanzas</span>
                    <span class="material-icon sidebar-group-arrow">expand_more</span>
                </button>
                <a class="sidebar-link {{ request()->query('section') === 'montos' ? 'active' : '' }}" href="{{ route('sistema', ['section' => 'montos']) }}">Montos</a>
                @can('clientes.debt')
                    <a class="sidebar-link {{ request()->query('section') === 'cuentas' ? 'active' : '' }}" href="{{ route('sistema', ['section' => 'cuentas']) }}">Cuentas por cobrar</a>
                @endcan
            </div>
        @endcan

        <div class="sidebar-group" data-group="registros">
            <button class="sidebar-group-label" type="button" data-toggle-group="registros">
                <span>Registros</span>
                <span class="material-icon sidebar-group-arrow">expand_more</span>
            </button>
            @can('clientes.manage')
                <a class="sidebar-link {{ request()->query('section') === 'clientes' ? 'active' : '' }}" href="{{ route('sistema', ['section' => 'clientes']) }}">Clientes</a>
            @endcan
            @can('transportistas.manage')
                <a class="sidebar-link {{ request()->query('section') === 'transportistas' ? 'active' : '' }}" href="{{ route('sistema', ['section' => 'transportistas']) }}">Transportistas</a>
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