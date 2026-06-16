# Plan: Refactor del Módulo de Asignación de Carga

## Problema actual

- Se cargan TODOS los envíos y TODOS los transportistas de una vez
- La columna "Sin asignar" muestra `EnvioCard` (envíos completos), no items sueltos
- Para separar items de un envío hay que arrastrar el envío completo primero a un transportista y luego mover items individuales de vuelta
- Si hay 15 transportistas, se muestran 15 columnas vacías

## Flujo nuevo deseado

```
PANTALLA 1: Lista de envíos pendientes de asignación
┌─────────────────────────────────────────────┐
│  Asignación de Carga                        │
│  Envíos con items pendientes de asignar      │
├─────────────────────────────────────────────┤
│  #1025 - Cliente Juan Pérez    4 items  →   │
│  #1028 - Cliente María López   3 items  →   │
│  #1030 - Cliente Carlos Ruiz   6 items  →   │
└─────────────────────────────────────────────┘
         │ click en un envío
         ▼
PANTALLA 2: Kanban del envío seleccionado
┌──────────────────────────────────────────────────────────┐
│  ← Volver    #1025 - Juan Pérez    [🔍 Transportista ▼]  │
├──────────────┬──────────────┬──────────────┬─────────────┤
│  #1025       │  Carlos      │  Juan        │  + Agregar  │
│  (items sin  │              │              │  transport. │
│   asignar)   │  [Asignar    │              │             │
│              │   todo]      │              │             │
│  ☐ 45 cajas  │              │  ☐ 15 cajas  │             │
│    Mostaza   │              │    Ketchup   │             │
│              │              │  ☐ 1 saco    │             │
│  ☐ 15 cajas  │              │    Arveja    │             │
│    Ketchup   │              │              │             │
│              │              │              │             │
│  ☐ 1 saco    │              │              │             │
│    Arveja    │              │              │             │
│              │              │              │             │
│  ☐ 8 cajas   │              │              │             │
│    Mayonesa  │              │              │             │
└──────────────┴──────────────┴──────────────┴─────────────┘
```

## Archivos a modificar

### 1. Backend: `AsignacionCargaController.php`

**Cambios:**
- `index()` → Solo retorna envíos con items sin asignar (pendientes), formato ligero para la lista
- Agregar `show(Envio $envio)` → Retorna UN envío específico con sus items + transportistas activos (para el kanban)

```php
// index() - Lista de pendientes
public function index(): JsonResponse
{
    $envios = Envio::with(['cliente', 'items.transportista'])
        ->whereHas('items', fn($q) => $q->whereNull('transportista_id'))
        ->latest('id')
        ->limit(100)
        ->get()
        ->map(fn($e) => [
            'id' => $e->id,
            'codigo' => $e->codigo,
            'fecha' => $e->fecha?->format('Y-m-d'),
            'cliente' => $e->cliente?->nombre,
            'total_items' => $e->items->count(),
            'items_sin_asignar' => $e->items->whereNull('transportista_id')->count(),
            'items_asignados' => $e->items->whereNotNull('transportista_id')->count(),
        ]);

    return response()->json(['ok' => true, 'envios' => $envios]);
}

// show() - Detalle de un envío para el kanban
public function show(Envio $envio): JsonResponse
{
    $envio->load(['cliente', 'items.transportista']);
    $transportistas = Transportista::where('activo', true)->orderBy('nombre')->get();

    return response()->json([
        'ok' => true,
        'envio' => $this->formatEnvio($envio),
        'transportistas' => $transportistas->map(fn($t) => $this->formatTransportista($t)),
    ]);
}
```

### 2. Backend: `routes/web.php`

**Agregar ruta:**
```php
Route::get('/asignacion-carga/{envio}', [AsignacionCargaController::class, 'show'])
    ->middleware('permission:envios.create');
```

### 3. Frontend: `hooks/useAsignacion.js`

**Reescribir completamente.** Nuevo estado:

```js
// Estado nuevo
const [enviosPendientes, setEnviosPendientes] = useState([]);  // Lista pantalla 1
const [envioActivo, setEnvioActivo] = useState(null);           // Envío seleccionado
const [transportistasDisponibles, setTransportistasDisponibles] = useState([]);  // Para search
const [columnasActivas, setColumnasActivas] = useState([]);     // Transportistas agregados al kanban
const [view, setView] = useState('lista');  // 'lista' | 'kanban'

// Funciones nuevas
const loadPendientes()      // GET /api/asignacion-carga → lista
const selectEnvio(envioId)  // GET /api/asignacion-carga/{id} → kanban
const volverALista()        // setView('lista'), limpia estado
const agregarTransportista(transportistaId)  // Agrega a columnasActivas[]
const quitarTransportista(transportistaId)   // Quita de columnasActivas[]
const asignarTodosATransportista(transportistaId)  // bulkAsignar todos los sin-asignar
```

### 4. Frontend: `App.jsx`

**Reescribir.** Renderizado condicional:

```jsx
{view === 'lista' ? (
    <EnviosPendientesList
        envios={enviosPendientes}
        onSelect={selectEnvio}
        loading={loading}
    />
) : (
    <KanbanBoard
        envio={envioActivo}
        columnasActivas={columnasActivas}
        transportistasDisponibles={transportistasDisponibles}
        onAgregarTransportista={agregarTransportista}
        onQuitarTransportista={quitarTransportista}
        onAsignarItem={asignarItem}
        onDesasignarItem={desasignarItem}
        onAsignarTodos={asignarTodosATransportista}
        onVolver={volverALista}
    />
)}
```

### 5. Frontend: Nuevo componente `EnviosPendientesList.jsx`

```jsx
// Lista de envíos con items sin asignar
// Cada fila muestra: código, cliente, fecha, total items, items sin asignar
// Click → selectEnvio(id)
// Badge de progreso: "3/5 sin asignar"
```

### 6. Frontend: Reescribir `KanbanBoard.jsx`

**Props nuevas:**
- `envio` (un solo envío, no array)
- `columnasActivas` (transportistas seleccionados por el usuario)
- `transportistasDisponibles` (para el search)

**Estructura:**
```jsx
<div className="kanban-toolbar">
    <button onClick={onVolver}>← Volver</button>
    <h2>{envio.codigo} - {envio.cliente}</h2>
    <TransportistaSearch
        disponibles={transportistasDisponibles}
        activas={columnasActivas}
        onAgregar={onAgregarTransportista}
    />
</div>
<div className="kanban-board">
    <EnvioItemsColumn items={itemsSinAsignar} envio={envio} />
    {columnasActivas.map(t => (
        <TransportistaColumn
            transportista={t}
            items={itemsDeTransportista(t.id)}
            envio={envio}
            onAsignarTodos={() => onAsignarTodos(t.id)}
            onQuitar={() => onQuitarTransportista(t.id)}
        />
    ))}
</div>
```

### 7. Frontend: Reemplazar `UnassignedPool.jsx` → `EnvioItemsColumn.jsx`

**Cambios:**
- Ya no muestra `EnvioCard`, muestra `ItemCard` directamente
- Título = código del envío (ej: "#1025")
- Subtítulo = nombre del cliente
- Solo muestra items donde `transportista_id === null`
- Es droppable (para devolver items)

### 8. Frontend: Modificar `TransportistaColumn.jsx`

**Agregar:**
- Botón "Asignar todo" en el header (llama `onAsignarTodos`)
- Botón "×" para quitar la columna (llama `onQuitar`)
- Ya no necesita `envios` array, solo `envio` (uno)

### 9. Frontend: Nuevo componente `TransportistaSearch.jsx`

```jsx
// Input con dropdown/autocomplete
// Muestra transportistas disponibles (que no están en columnasActivas)
// Al seleccionar → onAgregar(transportista)
// Filtra por nombre mientras escribe
```

### 10. Frontend: Eliminar `EnvioCard.jsx`

Ya no se usa (no arrastramos envíos completos, solo items individuales).

### 11. CSS: `resources/css/asignacion.css`

**Agregar estilos para:**
- `.envios-lista` - Tabla/lista de envíos pendientes
- `.envios-lista-row` - Cada fila clicable
- `.kanban-toolbar` - Barra superior del kanban (volver + título + search)
- `.transportista-search` - Input + dropdown
- `.transportista-search-dropdown` - Lista de resultados
- `.column-action-btn` - Botón "Asignar todo" y "×" en columnas

## Resumen de cambios por archivo

| Archivo | Acción | Descripción |
|---------|--------|-------------|
| `AsignacionCargaController.php` | Modificar | `index()` solo pendientes, nuevo `show()` |
| `routes/web.php` | Agregar 1 línea | `GET /asignacion-carga/{envio}` |
| `hooks/useAsignacion.js` | Reescribir | Estado dual (lista/kanban), columnas activas |
| `App.jsx` | Reescribir | Renderizado condicional lista vs kanban |
| `EnviosPendientesList.jsx` | **Crear** | Lista clickeable de envíos pendientes |
| `TransportistaSearch.jsx` | **Crear** | Input search con dropdown autocomplete |
| `EnvioItemsColumn.jsx` | **Crear** | Reemplaza UnassignedPool, muestra items sueltos |
| `KanbanBoard.jsx` | Reescribir | Un solo envío, columnas dinámicas |
| `TransportistaColumn.jsx` | Modificar | Botones "Asignar todo" y "×" |
| `UnassignedPool.jsx` | Eliminar | Reemplazado por EnvioItemsColumn |
| `EnvioCard.jsx` | Eliminar | Ya no se arrastran envíos completos |
| `ItemCard.jsx` | Sin cambios | Se mantiene igual |
| `asignacion.css` | Agregar | Estilos para lista, toolbar, search |

## Orden de implementación

1. Backend: controller + ruta
2. Hook: reescribir `useAsignacion.js`
3. Componente: `EnviosPendientesList.jsx`
4. Componente: `TransportistaSearch.jsx`
5. Componente: `EnvioItemsColumn.jsx`
6. Modificar: `TransportistaColumn.jsx` (botones)
7. Reescribir: `KanbanBoard.jsx`
8. Reescribir: `App.jsx`
9. Eliminar: `UnassignedPool.jsx`, `EnvioCard.jsx`
10. CSS + build

## Estado actual del proyecto

### Lo que ya funciona:
- Migración `transportista_id` en `envio_items` ✅
- Modelo `EnvioItem` con relación `transportista()` ✅
- Modelo `Transportista` con relación `items()` ✅
- Controlador `AsignacionCargaController` con endpoints CRUD ✅
- Rutas API funcionando ✅
- React + @dnd-kit instalados ✅
- Vite configurado con plugin React ✅
- Vista Blade `asignacion/index.blade.php` ✅
- Ruta `/asignacion-carga` funcionando ✅
- Link en sidebar ✅
- CSS base del kanban ✅

### Lo que hay que refactorizar:
- El flujo de 2 pantallas (lista → kanban por envío)
- Columnas dinámicas (solo las que el usuario agregue)
- Items sueltos en vez de EnvioCard
