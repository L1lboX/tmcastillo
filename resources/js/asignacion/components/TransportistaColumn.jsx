import { useDroppable } from '@dnd-kit/core';
import { ItemCard } from './ItemCard';

export function TransportistaColumn({ transportista, items, envio, onAsignarTodos, onQuitar, onSplitClick, onMergeClick }) {
    const { setNodeRef, isOver } = useDroppable({
        id: `transportista-${transportista.id}`,
        data: { type: 'transportista', transportista },
    });

    return (
        <div className={`kanban-column ${isOver ? 'kanban-column-over' : ''}`}>
            <header className="kanban-column-header">
                <div className="kanban-column-title">
                    <h3>{transportista.nombre}</h3>
                </div>
                <div className="kanban-column-header-actions">
                    <span className="kanban-column-count">{items.length}</span>
                    <button
                        className="column-action-btn column-action-btn-assign"
                        onClick={onAsignarTodos}
                        title="Asignar todo"
                    >
                        Asignar todo
                    </button>
                    <button
                        className="column-action-btn column-action-btn-remove"
                        onClick={onQuitar}
                        title="Quitar columna"
                    >
                        ×
                    </button>
                </div>
            </header>
            <div ref={setNodeRef} className="kanban-column-body">
                {items.length === 0 ? (
                    <div className="kanban-column-empty">
                        Arrastra items aquí
                    </div>
                ) : (
                    items.map((item) => (
                        <ItemCard key={item.id} item={item} envio={envio} onSplitClick={onSplitClick} onMergeClick={onMergeClick}/>
                    ))
                )}
            </div>
        </div>
    );
}
