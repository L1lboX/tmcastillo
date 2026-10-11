import { DndContext, DragOverlay, closestCorners } from '@dnd-kit/core';
import { useState } from 'react';
import { TransportistaColumn } from './TransportistaColumn';
import { EnvioItemsColumn } from './EnvioItemsColumn';
import { TransportistaSearch } from './TransportistaSearch';
import { ItemCard } from './ItemCard';
import { SplitItemModal } from './SplitItemModal';

export function KanbanBoard({
    envio,
    columnasActivas,
    transportistasDisponibles,
    onAgregarTransportista,
    onQuitarTransportista,
    onAsignarItem,
    onDesasignarItem,
    onAsignarTodos,
    onSplitItem,
    onMergeItems,
    onVolver,
}) {
    const [activeItem, setActiveItem] = useState(null);
    const [splittingItem, setSplittingItem] = useState(null);

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

       if (dragData?.type !== 'item') return;

        const itemOrigen = dragData.item;

        if (dropData?.type === 'item') {
            const itemDestino = dropData.item;

           if (itemOrigen && itemDestino && itemOrigen.id !== itemDestino.id) {
                const descOrigen = (itemOrigen.descripcion || '').trim().toLowerCase();
                const descDestino = (itemDestino.descripcion || '').trim().toLowerCase();

                if (descOrigen === descDestino && typeof onMergeItems === 'function') {
                    await onMergeItems(itemOrigen.id, itemDestino.id);
                    return;
                }

                if (!itemDestino.transportista_id && itemOrigen.transportista_id) {
                    await onDesasignarItem(itemOrigen.id);
                    return;
                }

                if (itemDestino.transportista_id && itemOrigen.transportista_id !== itemDestino.transportista_id) {
                    await onAsignarItem(itemOrigen.id, itemDestino.transportista_id);
                    return;
                }
            }
            return;
        }

        if (dropData?.type === 'transportista') {
            if (itemOrigen.transportista_id !== dropData.transportista.id) {
                await onAsignarItem(itemOrigen.id, dropData.transportista.id);
            }
            return;
        }
        
        if (dropData?.type === 'unassigned') {
            if (itemOrigen.transportista_id) {
                await onDesasignarItem(itemOrigen.id);
            }
            return;
        }
    }

    function handleOpenSplit(item, itemEnvio) {
        setSplittingItem({ item, envio: itemEnvio || envio });
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
                <EnvioItemsColumn 
                    envio={envio} 
                    items={envio.items}
                    onSplitClick={handleOpenSplit}
                    onMergeClick={onMergeItems}
                />
                {columnasActivas.map((t) => (
                    <TransportistaColumn
                        key={t.id}
                        transportista={t}
                        items={getItemsByTransportista(t.id)}
                        envio={envio}
                        onAsignarTodos={() => onAsignarTodos(t.id)}
                        onQuitar={() => onQuitarTransportista(t.id)}
                        onSplitClick={handleOpenSplit}
                        onMergeClick={onMergeItems}
                    />
                ))}
            </div>

            <DragOverlay>
                {activeItem?.type === 'item' && (
                    <ItemCard item={activeItem.item} envio={activeItem.envio} />
                )}
            </DragOverlay>

            {splittingItem && (
                <SplitItemModal
                    isOpen={true}
                    item={splittingItem.item}
                    envio={splittingItem.envio}
                    onClose={() => setSplittingItem(null)}
                    onConfirm={onSplitItem}
                />
            )}
            
        </DndContext>
    );
}
