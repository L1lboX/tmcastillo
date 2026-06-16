import { DndContext, DragOverlay, closestCorners } from '@dnd-kit/core';
import { useState } from 'react';
import { TransportistaColumn } from './TransportistaColumn';
import { EnvioItemsColumn } from './EnvioItemsColumn';
import { TransportistaSearch } from './TransportistaSearch';
import { ItemCard } from './ItemCard';

export function KanbanBoard({
    envio,
    columnasActivas,
    transportistasDisponibles,
    onAgregarTransportista,
    onQuitarTransportista,
    onAsignarItem,
    onDesasignarItem,
    onAsignarTodos,
    onVolver,
}) {
    const [activeItem, setActiveItem] = useState(null);

    function getItemsByTransportista(transportistaId) {
        return envio.items.filter((item) => item.transportista_id === transportistaId);
    }

    function handleDragStart(event) {
        const { active } = event;
        const data = active.data.current;
        setActiveItem(data);
    }

    async function handleDragEnd(event) {
        const { active, over } = event;
        setActiveItem(null);

        if (!over) return;

        const dragData = active.data.current;
        const dropData = over.data.current;

        if (dragData.type !== 'item') return;

        const item = dragData.item;

        if (dropData.type === 'transportista') {
            if (item.transportista_id !== dropData.transportista.id) {
                await onAsignarItem(item.id, dropData.transportista.id);
            }
        } else if (dropData.type === 'unassigned') {
            if (item.transportista_id) {
                await onDesasignarItem(item.id);
            }
        }
    }

    return (
        <DndContext
            collisionDetection={closestCorners}
            onDragStart={handleDragStart}
            onDragEnd={handleDragEnd}
        >
            <div className="kanban-toolbar">
                <button className="kanban-volver-btn" onClick={onVolver}>
                    ← Volver
                </button>
                <h2 className="kanban-toolbar-title">
                    #{envio.codigo || envio.id} - {envio.cliente}
                </h2>
                <TransportistaSearch
                    disponibles={transportistasDisponibles}
                    activas={columnasActivas}
                    onAgregar={onAgregarTransportista}
                />
            </div>

            <div className="kanban-board">
                <EnvioItemsColumn envio={envio} items={envio.items} />
                {columnasActivas.map((t) => (
                    <TransportistaColumn
                        key={t.id}
                        transportista={t}
                        items={getItemsByTransportista(t.id)}
                        envio={envio}
                        onAsignarTodos={() => onAsignarTodos(t.id)}
                        onQuitar={() => onQuitarTransportista(t.id)}
                    />
                ))}
            </div>

            <DragOverlay>
                {activeItem?.type === 'item' && (
                    <ItemCard item={activeItem.item} envio={activeItem.envio} />
                )}
            </DragOverlay>
        </DndContext>
    );
}
