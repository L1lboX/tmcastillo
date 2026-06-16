# Refactor del Módulo de Asignación de Carga - Completado

Refactor completo. Build exitoso sin errores.

## Resumen de cambios

### Backend
- `AsignacionCargaController.php` - `index()` ahora solo retorna envíos con items sin asignar (formato ligero), nuevo `show()` para kanban por envío
- `routes/web.php` - Agregada ruta `GET /asignacion-carga/{envio}`

### Frontend (nuevos)
- `EnviosPendientesList.jsx` - Lista clickeable de envíos pendientes con barra de progreso
- `TransportistaSearch.jsx` - Input con dropdown autocomplete para agregar transportistas
- `EnvioItemsColumn.jsx` - Reemplaza UnassignedPool, muestra items sueltos

### Frontend (modificados)
- `useAsignacion.js` - Reescrito con estado dual (lista/kanban), columnas activas dinámicas
- `KanbanBoard.jsx` - Un solo envío, columnas dinámicas, toolbar con search
- `TransportistaColumn.jsx` - Botones "Asignar todo" y "×" para quitar columna
- `App.jsx` - Renderizado condicional lista vs kanban

### Eliminados
- `UnassignedPool.jsx`
- `EnvioCard.jsx`

### CSS
- Estilos para lista, toolbar, search dropdown, botones de columna
