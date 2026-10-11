import { useEffect } from 'react';
import { KanbanBoard } from './components/KanbanBoard';
import { EnviosPendientesList } from './components/EnviosPendientesList';
import { useAsignacion } from './hooks/useAsignacion';

export function App() {
    const {
        enviosPendientes,
        envioActivo,
        transportistasDisponibles,
        columnasActivas,
        view,
        loading,
        error,
        toast,
        loadPendientes,
        selectEnvio,
        volverALista,
        agregarTransportista,
        quitarTransportista,
        asignarItem,
        desasignarItem,
        asignarTodosATransportista,
        splitItem,
        mergeItems,
    } = useAsignacion();

    useEffect(() => {
        loadPendientes();
    }, [loadPendientes]);

    return (
        <div className="kanban-app">
            <header className="kanban-header">
                <div className="kanban-header-title">
                    <h1>Asignación de Carga</h1>
                    <p>
                        {view === 'lista'
                            ? 'Envíos con items pendientes de asignación'
                            : 'Distribuye los items del envío entre transportistas'}
                    </p>
                </div>
                <div className="kanban-header-actions">
                    <button
                        className="kanban-refresh-btn"
                        onClick={view === 'lista' ? loadPendientes : volverALista}
                        disabled={loading}
                    >
                        {loading ? 'Cargando...' : view === 'lista' ? 'Actualizar' : '← Lista'}
                    </button>
                </div>
            </header>

            {error && (
                <div className="kanban-error">
                    <p>{error}</p>
                    <button onClick={loadPendientes}>Reintentar</button>
                </div>
            )}

            {view === 'lista' ? (
                <EnviosPendientesList
                    envios={enviosPendientes}
                    onSelect={selectEnvio}
                    loading={loading}
                />
            ) : envioActivo ? (
                <KanbanBoard
                    envio={envioActivo}
                    columnasActivas={columnasActivas}
                    transportistasDisponibles={transportistasDisponibles}
                    onAgregarTransportista={agregarTransportista}
                    onQuitarTransportista={quitarTransportista}
                    onAsignarItem={asignarItem}
                    onDesasignarItem={desasignarItem}
                    onAsignarTodos={asignarTodosATransportista}
                    onSplitItem={splitItem}
                    onMergeItems={mergeItems}
                    onVolver={volverALista}
                />
            ) : (
                <div className="kanban-loading">
                    <div className="kanban-spinner" />
                    <p>Cargando envío...</p>
                </div>
            )}

            {toast && (
                <div className={`kanban-toast kanban-toast-${toast.type}`}>
                    {toast.message}
                </div>
            )}
        </div>
    );
}
